<?php

namespace App\Support;

use InvalidArgumentException;

/** Gambar ditolak sebelum didekode karena resolusinya bisa menghabiskan memori worker. */
final class ImageTooLarge extends InvalidArgumentException {}
