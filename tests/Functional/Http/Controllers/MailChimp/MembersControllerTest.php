<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;
use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test application creates successfully member and returns it back with id from MailChimp.
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        // create the list
        $this->post('/mailchimp/lists', static::$listData);
        // get list response
        $list = \json_decode($this->response->getContent(), true);
        // create the member
        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']), static::$memberData);
        // get member response
        $member = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        $this->seeJson(static::$memberData);
        self::assertArrayHasKey('mail_chimp_member_id', $member);
        self::assertNotNull($member['mail_chimp_member_id']);

        // Store MailChimp list id and member id
        $this->createdMembers[$list['mail_chimp_id']][] = $member['mail_chimp_member_id']; 
    }

    /**
     * Test application returns error response with errors when list validation fails.
     *
     * @return void
     */
    public function testCreateMemberValidationFailed(): void
    {
        // create the list
        $this->post('/mailchimp/lists', static::$listData);
        // get list response
        $list = \json_decode($this->response->getContent(), true);
        // create member WITHOUT request data
        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']));
        // get member response
        $member = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(422);
        self::assertArrayHasKey('message', $member);
        self::assertArrayHasKey('errors', $member);
        self::assertEquals('Unprocessable Entity', $member['message']);

        foreach (\array_keys(static::$memberData) as $key) {
            if (\in_array($key, static::$notRequired, true)) {
                continue;
            }

            self::assertArrayHasKey($key, $member['errors']);
        }
    }

    /**
     * Test application returns error response when list not found.
     *
     * @return void
     */
    public function testRemoveMemberNotFoundException(): void
    {
        // create the list
        $this->post('/mailchimp/lists', static::$listData);
        // get list response
        $list = \json_decode($this->response->getContent(), true);

        $this->delete(\sprintf('/mailchimp/lists/%s/members/invalid-member-id', $list['list_id']));

        $this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns empty successful response when removing existing list.
     *
     * @return void
     */
    public function testRemoveMemberSuccessfully(): void
    {
        // create the list
        $this->post('/mailchimp/lists', static::$listData);
        // get list response
        $list = \json_decode($this->response->getContent(), true);
        // create member WITHOUT request data
        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']), static::$memberData);
        // get member response
        $member = \json_decode($this->response->getContent(), true);

        // delete the member first.
        $this->delete(\sprintf('/mailchimp/lists/%s/members/%s', $list['list_id'], $member['member_id']));
        $this->delete(\sprintf('/mailchimp/lists/%s', $list['list_id']));

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));

    }

    /**
     * Test application returns error response when list not found.
     *
     * @return void
     */
    public function testShowMemberNotFoundException(): void
    {
        // create the list
        $this->post('/mailchimp/lists', static::$listData);
        // get list response
        $list = \json_decode($this->response->getContent(), true);

        $this->get(\sprintf('/mailchimp/lists/%s/members/invalid-member-id', $list['list_id']));

        $this->assertMemberNotFoundResponse('invalid-member-id');
        $this->delete(\sprintf('/mailchimp/lists/%s', $list['list_id']));
    }

    /**
     * Test application returns successful response with list data when requesting existing list.
     *
     * @return void
     */
    public function testShowMemberSuccessfully(): void
    {
        // create list - regular save (no mailchimp)
        $list = $this->createList(static::$listData);
        // create member - regular save
        $member = $this->createMember($list, static::$memberData);

        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), $member->getId()));
        $response = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (static::$memberData as $key => $value) {
            self::assertArrayHasKey($key, $response);
            self::assertEquals($value, $response[$key]);
        }
    }

    /**
     * Test application returns error response when list not found.
     *
     * @return void
     */
    public function testUpdateMemberNotFoundException(): void
    {
        // DO Post request for member
        $this->post('/mailchimp/lists', static::$listData);
        // Get response
        $list = \json_decode($this->response->getContent(), true);
        // do put request for member
        $this->put(\sprintf('/mailchimp/lists/%s/members/invalid-member-id', $list['list_id']));

        $this->assertMemberNotFoundResponse('invalid-member-id');

        // delete the created list.
        $this->delete(\sprintf('/mailchimp/lists/%s', $list['list_id']));
    }

    /**
     * Test application returns successfully response when updating existing list with updated values.
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
        // DO Post request for member
        $this->post('/mailchimp/lists', static::$listData);
        // Get response
        $list = \json_decode($this->response->getContent(), true);
        // create the member
        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']), static::$memberData);
        // get response
        $member = \json_decode($this->response->getContent(), true);
        // Store MailChimp member id for cleaning purposes
        $this->createdMembers[$list['mail_chimp_id']][] = $member['mail_chimp_member_id']; 
        // do PUT request to do valid update
        $this->put(\sprintf('/mailchimp/lists/%s/members/%s', $list['list_id'], $member['member_id']), ['email_type' => 'email']);
        // get response
        $response = \json_decode($this->response->content(), true);
        // success
        $this->assertResponseOk();

        foreach (\array_keys(static::$memberData) as $key) {
            self::assertArrayHasKey($key, $response);
            self::assertEquals('email', $response['email_type']);
        }
    }

    /**
     * Test application returns error response with errors when list validation fails.
     *
     * @return void
     */
    public function testUpdateMemberValidationFailed(): void
    {
        // create list - regular save (no mailchimp)
        $list = $this->createList(static::$listData);
        // create member - regular save
        $member = $this->createMember($list, static::$memberData);
        // try to update with invalid parameter
        $this->put(\sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), $member->getId()), ['status' => 'invalid']);
        // get content
        $content = \json_decode($this->response->content(), true);
        // check responses
        $this->assertResponseStatus(422);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertArrayHasKey('status', $content['errors']);
        self::assertEquals('Unprocessable Entity', $content['message']);
    }
}
