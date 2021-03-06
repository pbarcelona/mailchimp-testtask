<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;
use Illuminate\Http\Response;

class ListsController extends Controller
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
     * Create MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Instantiate entity
        $list = new MailChimpList($request->all());
        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

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
            // begin the transaction, to keep the table in sync with the list from mailchimp
            $entityManager->transactional(function($entityManager) use ($list){
                // Save list into db
                $this->saveEntity($list);
                // Save list into MailChimp
                $response = $this->mailChimp->post('lists', $list->toMailChimpArray());
                // Set MailChimp id on the list and save list into db
                $this->saveEntity($list->setMailChimpId($response->get('id')));
            });
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Remove MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        try {
            // Create a new instance of the entity manager
            $entityManager = $this->entityManager;
            // begin the transaction, to keep the table in sync with the list from mailchimp
            $entityManager->transactional(function($entityManager) use ($list){
                // Remove list from database
                $this->removeEntity($list);
                // Remove list from MailChimp
                $this->mailChimp->delete(\sprintf('lists/%s', $list->getMailChimpId()));
            });
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * Retrieve and return MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Update MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            // no record found
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        // Update list properties
        $list->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

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
            // begin the transaction, to keep the table in sync with the list from mailchimp
            $entityManager->transactional(function($entityManager) use ($list){
                // Update list into database
                $this->saveEntity($list);
                // Update list into MailChimp
                $this->mailChimp->patch(\sprintf('lists/%s', $list->getMailChimpId()), $list->toMailChimpArray());
            });

        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }
}
