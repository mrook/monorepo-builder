<?php

declare (strict_types=1);
namespace MonorepoBuilder20211008\Symplify\PackageBuilder\Console;

use MonorepoBuilder20211008\Symfony\Component\Console\Command\Command;
/**
 * @deprecated Use symfony constants in directly
 * @see Command::FAILURE
 * @see Command::SUCCESS
 */
final class ShellCode
{
    /**
     * @var int
     *
     * @deprecated Use symfony constants in directly
     * @see Command::SUCCESS
     */
    public const SUCCESS = 0;
    /**
     * @var int
     *
     * @deprecated Use symfony constants in directly
     * @see Command::FAILURE
     */
    public const ERROR = 1;
}
