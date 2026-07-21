<?php
declare(strict_types=1);
namespace App\Core;
use RuntimeException;

/** A safe, user-facing error raised by a model or service operation. */
class ServiceException extends RuntimeException
{
}
