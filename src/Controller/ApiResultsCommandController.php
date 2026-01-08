<?php

namespace App\Controller;

use App\Entity\{Result, Subject, User};
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

use function in_array;

#[Route(
    path: ApiResultsQueryInterface::RUTA_API,
    name: 'api_results_'
)]
final class ApiResultsCommandController extends AbstractController implements ApiResultsCommandInterface
{
    private const string ROLE_ADMIN = 'ROLE_ADMIN';

    // JSON keys（需与你 OpenAPI 一致；也与你实体字段一致）
    private const string KEY_SCORE_VALUE    = 'scoreValue';
    private const string KEY_PASSED         = 'passed';
    private const string KEY_GRADED_AT      = 'gradedAt';
    private const string KEY_ATTEMPT_NUMBER = 'attemptNumber';
    private const string KEY_REMARKS        = 'remarks';
    private const string KEY_SUBJECT_ID     = 'subjectId';
    private const string KEY_USER_ID        = 'userId'; // 仅 ROLE_ADMIN 可用（可选）

    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * 本项目 Users Controller 的鉴权风格：显式检查 IS_AUTHENTICATED_FULLY
     */
    private function checkAuthUser(string $format): ?Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                'UNAUTHORIZED: Invalid credentials.',
                $format
            );
        }
        return null;
    }

    /**
     * ROLE_USER 只能操作自己的 result；ROLE_ADMIN 允许全部
     */
    private function checkResultOwnerOrAdmin(Result $result, User $authUser, string $format): ?Response
    {
        if ($this->isGranted(self::ROLE_ADMIN)) {
            return null;
        }

        $owner = $result->getUser();
        if (!$owner instanceof User || $owner->getId() !== $authUser->getId()) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                'FORBIDDEN: you don\'t have permission to access',
                $format
            );
        }

        return null;
    }

    /**
     * POST /api/v1/results
     */
    #[Route(
        path: ".{_format}",
        name: 'post',
        requirements: [
            '_format' => "json|xml"
        ],
        defaults: [
            '_format' => null
        ],
        methods: [Request::METHOD_POST]
    )]
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);

        if ($response = $this->checkAuthUser($format)) {
            return $response;
        }

        /** @var User $authUser */
        $authUser = $this->getUser();

        $payload = $request->getPayload();

        // Result.php 中 remarks 为非空 string（#[ORM\Column(length:255)]），所以这里也必须必填
        $required = [
            self::KEY_SCORE_VALUE,
            self::KEY_PASSED,
            self::KEY_GRADED_AT,
            self::KEY_ATTEMPT_NUMBER,
            self::KEY_REMARKS,
            self::KEY_SUBJECT_ID,
        ];

        foreach ($required as $key) {
            if (!in_array($key, $payload->keys(), true) || $payload->get($key) === null || $payload->get($key) === '') {
                return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
            }
        }

        // 解析 subject
        $subjectId = (int) $payload->get(self::KEY_SUBJECT_ID);
        /** @var Subject|null $subject */
        $subject = $this->entityManager->getRepository(Subject::class)->find($subjectId);
        if (!$subject instanceof Subject) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
        }

        // gradedAt: ISO 8601 date-time
        try {
            $gradedAt = new \DateTimeImmutable((string) $payload->get(self::KEY_GRADED_AT));
        } catch (\Throwable) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
        }

        // owner 规则：ROLE_USER 固定为当前用户；ROLE_ADMIN 可选传 userId 指定 owner
        $owner = $authUser;
        if ($this->isGranted(self::ROLE_ADMIN)
            && in_array(self::KEY_USER_ID, $payload->keys(), true)
            && $payload->get(self::KEY_USER_ID) !== null
            && $payload->get(self::KEY_USER_ID) !== ''
        ) {
            $userId = (int) $payload->get(self::KEY_USER_ID);
            /** @var User|null $targetUser */
            $targetUser = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$targetUser instanceof User) {
                return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
            }
            $owner = $targetUser;
        }

        $result = new Result();
        $result->setScoreValue((float) $payload->get(self::KEY_SCORE_VALUE));
        $result->setPassed((bool) $payload->get(self::KEY_PASSED));
        $result->setGradedAt($gradedAt);
        $result->setAttemptNumber((int) $payload->get(self::KEY_ATTEMPT_NUMBER));
        $result->setRemarks((string) $payload->get(self::KEY_REMARKS));
        $result->setSubject($subject);
        $result->setUser($owner);

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ 'result' => $result ],
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost()
                    . ApiResultsQueryInterface::RUTA_API . '/' . $result->getId()
            ]
        );
    }

    /**
     * PUT /api/v1/results/{resultId}
     */
    #[Route(
        path: "/{resultId}.{_format}",
        name: 'put',
        requirements: [
            'resultId' => '\d+',
            '_format'  => "json|xml"
        ],
        defaults: [
            '_format' => null
        ],
        methods: [Request::METHOD_PUT]
    )]
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);

        if ($response = $this->checkAuthUser($format)) {
            return $response;
        }

        /** @var User $authUser */
        $authUser = $this->getUser();

        /** @var Result|null $result */
        $result = $this->entityManager->getRepository(Result::class)->find($resultId);
        if (!$result instanceof Result) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        // 权限：ROLE_ADMIN 任意；ROLE_USER 仅本人
        if ($response = $this->checkResultOwnerOrAdmin($result, $authUser, $format)) {
            return $response;
        }

        $payload = $request->getPayload();

        // PUT 采用“全量更新”语义：缺必填字段 => 400
        $required = [
            self::KEY_SCORE_VALUE,
            self::KEY_PASSED,
            self::KEY_GRADED_AT,
            self::KEY_ATTEMPT_NUMBER,
            self::KEY_REMARKS,
            self::KEY_SUBJECT_ID,
        ];

        foreach ($required as $key) {
            if (!in_array($key, $payload->keys(), true) || $payload->get($key) === null || $payload->get($key) === '') {
                return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
            }
        }

        // subject 更新：为了符合“ROLE_USER 只能操作自己的结果”且避免横向越权，普通用户不允许改 subject
        $subjectId = (int) $payload->get(self::KEY_SUBJECT_ID);
        /** @var Subject|null $subject */
        $subject = $this->entityManager->getRepository(Subject::class)->find($subjectId);
        if (!$subject instanceof Subject) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
        }
        if ($this->isGranted(self::ROLE_ADMIN)) {
            $result->setSubject($subject);
        } else {
            $currentSubject = $result->getSubject();
            if ($currentSubject instanceof Subject && $currentSubject->getId() !== $subject->getId()) {
                return Utils::errorMessage(
                    Response::HTTP_FORBIDDEN,
                    'FORBIDDEN: you don\'t have permission to access',
                    $format
                );
            }
        }

        // gradedAt
        try {
            $gradedAt = new \DateTimeImmutable((string) $payload->get(self::KEY_GRADED_AT));
        } catch (\Throwable) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
        }

        $result->setScoreValue((float) $payload->get(self::KEY_SCORE_VALUE));
        $result->setPassed((bool) $payload->get(self::KEY_PASSED));
        $result->setGradedAt($gradedAt);
        $result->setAttemptNumber((int) $payload->get(self::KEY_ATTEMPT_NUMBER));
        $result->setRemarks((string) $payload->get(self::KEY_REMARKS));

        // 允许 admin 可选改 owner（默认禁用更安全；若你需要开启我下一条再给你开关版）
        if (in_array(self::KEY_USER_ID, $payload->keys(), true) && $payload->get(self::KEY_USER_ID) !== null && $payload->get(self::KEY_USER_ID) !== '') {
            if (!$this->isGranted(self::ROLE_ADMIN)) {
                return Utils::errorMessage(
                    Response::HTTP_FORBIDDEN,
                    'FORBIDDEN: you don\'t have permission to access',
                    $format
                );
            }
            // 如确实需要管理员更改 owner，可在下一条我给你启用并补齐验证用例
        }

        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'result' => $result ],
            $format
        );
    }
}
