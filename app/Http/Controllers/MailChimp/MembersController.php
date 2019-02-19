<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpListMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;
use Illuminate\Http\Response;

class MembersController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * ListsController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Create MailChimp list member.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, string $listId): JsonResponse
    {
        // Instantiate entity
        $member = new MailChimpListMember($request->all());

        // Check if we're adding the member on a valid list.
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        // No list found?
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        // If got here, means that it is a valid list
        $member->setList($list);

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // abort(422); // - abort 422 causing 500 error
            // there's some kind of bug with ValidationException? 
            // Return error response if validation failed
            return $this->errorResponse([
                'success' => false,
                'status' => 422,
                'response_code' => "HTTP_UNPROCESSABLE_ENTITY",
                'message' => Response::$statusTexts[422],
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        try {
            // Create a new instance of the entity manager
            $entityManager = $this->entityManager;
            // begin the transaction, to keep the table in sync with the member from mailchimp
            $entityManager->transactional(function($entityManager) use ($member, $list, $request){
                // Save member into db
                $this->saveEntity($member);
                // Save member into MailChimp
                $response = $this->mailChimp->post(\sprintf('lists/%s/members', $list->getMailChimpId()), $request->all());
                // Set MailChimp member id on the member and save member into db
                $this->saveEntity($member->setMailChimpMemberId($response->get('id')));
            });

        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Remove MailChimp list.
     *
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId, string $memberId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpListMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpListMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpListMember[%s] not found', $memberId)],
                404
            );
        }

        try {
            // Create a new instance of the entity manager
            $entityManager = $this->entityManager;
            // begin the transaction, to keep the table in sync with the member from mailchimp
            $entityManager->transactional(function($entityManager) use ($list, $member){
                // Remove list from database
                $this->removeEntity($member);
                // Remove list from MailChimp
                $this->mailChimp->delete(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpMemberId()));
            });
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * Retrieve and return MailChimp member.
     *
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId, string $memberId): JsonResponse
    {
        // Check if we're adding the member on a valid list.
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        // No list found?
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpListMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpListMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpListMember[%s] not found', $memberId)],
                404
            );
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Update MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId, string $memberId): JsonResponse
    {
        // Check if we're adding the member on a valid list.
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        // No list found?
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpListMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpListMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpListMember[%s] not found', $memberId)],
                404
            );
        }

        // Update list properties
        $member->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // abort(422); // - abort 422 causing 500 error
            // there's some kind of bug with ValidationException? 
            // Return error response if validation failed
            return $this->errorResponse([
                'success' => false,
                'status' => 422,
                'response_code' => "HTTP_UNPROCESSABLE_ENTITY",
                'message' => Response::$statusTexts[422],
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        try {
            // Create a new instance of the entity manager
            $entityManager = $this->entityManager;
            // begin the transaction, to keep the table in sync with the member from mailchimp
            $entityManager->transactional(function($entityManager) use ($member, $list){
                // Update member into database
                $this->saveEntity($member);
                // Update member into MailChimp
                $this->mailChimp->patch(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $member->getMailChimpMemberId()), $member->toMailChimpArray());
            });

        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }
}
