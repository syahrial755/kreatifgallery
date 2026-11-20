<?php

namespace App\Exceptions;

use RuntimeException;

/** Dilempar bila file media gagal disimpan/diproses (mis. gambar rusak). Seluruh perubahan DB di-rollback. */
class MediaProcessingException extends RuntimeException
{
}
