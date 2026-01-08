<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Interface ApiResultsQueryInterface
 *
 * 仅用于提供 Results API 的基础路径常量（与 Users 同风格）
 */
interface ApiResultsQueryInterface
{
    public final const string RUTA_API = '/api/v1/results';

    // 你已实现 GET/GET{id}/OPTIONS/DELETE，这里先不声明这些方法，避免与你现有实现冲突
    // P4 仅需要 RUTA_API 给 CommandController 做 class-level route prefix
}
