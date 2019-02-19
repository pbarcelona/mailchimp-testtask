<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';

    /**
     * @var array
     */
    protected $createdMembers = [];

    /**
     * @var array - made this non static for emails.
     */
    protected static $memberData = [
        "email_address" => "emailfortest5@gmail.com",
        "email_type" => "text",
        "status" => "subscribed",
        "merge_fields" => [
          "FNAME"=> "",
          "LNAME"=> ""
        ],
        "language"=> "en",
        "vip"=> false,
        "location" => [
            "longitude" => 1,
            "latitude" => 1
        ],
        "marketing_permissions" => [],
        "ip_signup" => "192.168.10.10",
        "ip_opt" => "192.168.10.10",
        "tags" => []
    ];

    /**
     * @var array
     */
    protected static $listData = [
        'name' => 'Random list By Members',
        'permission_reminder' => 'You signed up for updates on Greeks economy.',
        'email_type_option' => false,
        'contact' => [
            'company' => 'Doe Ltd.',
            'address1' => 'DoeStreet 1',
            'address2' => '',
            'city' => 'Doesy',
            'state' => 'Doedoe',
            'zip' => '1672-12',
            'country' => 'US',
            'phone' => '55533344412'
        ],
        'campaign_defaults' => [
            'from_name' => 'John Doe',
            'from_email' => 'john@doe.com',
            'subject' => 'My new campaign!',
            'language' => 'US'
        ],
        'visibility' => 'prv',
        'use_archive_bar' => false,
        'notify_on_subscribe' => 'notify@loyaltycorp.com.au',
        'notify_on_unsubscribe' => 'notify@loyaltycorp.com.au'
    ];

    /**
     * @var array
     */
    protected static $notRequired = [
        'list_id',
        'email_type',
        'mail_chimp_member_id',
        'merge_fields',
        'interests',
        'language',
        'vip',
        'location',
        'marketing_permissions',
        'ip_signup',
        'ip_opt',
        'tags',
    ];

    /**
     * Call MailChimp to delete members created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);
        foreach ($this->createdMembers as $listId => $members) {
            // delete members first
            foreach($members as $memberId) {
                $mailChimp->delete(\sprintf('lists/%s/members/%s', $listId, $memberId));
            }
            // Delete list on MailChimp after deleting members
            $mailChimp->delete(\sprintf('lists/%s', $listId));            
        }

        parent::tearDown();
    }

    /**
     * Asserts error response when member not found.
     *
     * @param string $memberId
     *
     * @return void
     */
    protected function assertMemberNotFoundResponse(string $memberId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpListMember[%s] not found', $memberId), $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return void
     */
    protected function assertMailChimpExceptionResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(self::MAILCHIMP_EXCEPTION_MESSAGE, $content['message']);
    }

    /**
     * Create MailChimp member into database.
     *
     * @param array $listData
     * @param array $memberData
     *
     * @return \App\Database\Entities\MailChimp\MailChimpListMember
     */
    protected function createMember(MailChimpList $list, array $memberData): MailChimpListMember
    {
        $member = new MailChimpListMember($memberData);
        $member->setList($list);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForException(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(self::MAILCHIMP_EXCEPTION_MESSAGE));

        return $mailChimp;
    }

    /**
     * Create MailChimp list into database.
     *
     * @param array $data
     *
     * @return array
     */
    protected function createList(array $data): MailChimpList
    {
        // Instantiate List
        $list = new MailChimpList(static::$listData);
        // save
        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }
}
