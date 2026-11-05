<?php

namespace DrobnyDev\ApplicationVersioning\Exceptions;

use RuntimeException;

/**
 * version.yaml is missing, cannot be parsed, or lacks version.current.major,
 * minor, patch (integers) or format (string).
 */
class VersionFileException extends RuntimeException {}
