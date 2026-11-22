<?php

namespace App\Exceptions;

use RuntimeException;

/** Dasar semua penolakan sewa karena melanggar aturan bisnis; pesannya aman ditampilkan ke pengguna. */
class AturanSewaException extends RuntimeException {}
