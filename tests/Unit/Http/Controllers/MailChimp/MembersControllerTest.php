<?php
declare(strict_types=1);

namespace Tests\App\Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\MembersController;
use App\Http\Controllers\MailChimp\ListsController;
use Tests\App\TestCases\MailChimp\MemberTestCase;
use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        // create the list
        // create list - regular save (no mailchimp)
        $list = $this->createList(static::$listData);
        // create member - regular save
        $member = $this->createMember($list, static::$memberData);

        $memberController = new MembersController($this->entityManager, $this->mockMailChimpForException('post'));

        $this->assertMailChimpExceptionResponse($memberController->create($this->getRequest(static::$memberData), $list->getId()));
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $memberController = new MembersController($this->entityManager, $this->mockMailChimpForException('delete'));
        // create list - regular save (no mailchimp)
        $list = $this->createList(static::$listData);
        // create member - regular save
        $member = $this->createMember($list, static::$memberData);

        // If there is no list id, skip
        if (null === $member->getId()) {
            self::markTestSkipped('Unable to remove, no id provided for list');

            return;
        }

        $this->assertMailChimpExceptionResponse($memberController->remove($list->getId(), $member->getId()));
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('patch'));
        // create list - regular save (no mailchimp)
        $list = $this->createList(static::$listData);
        // create member - regular save
        $member = $this->createMember($list, static::$memberData);

        // If there is no list id, skip
        if (null === $member->getId()) {
            self::markTestSkipped('Unable to update, no id provided for member');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->update($this->getRequest(), $list->getId(), $member->getId()));
    }
}
