<?php

namespace App\enums;

use PhpParser\Node\Expr\Cast\String_;

enum UserRole: String {

    case admin = 'admin';
    case siswa = 'siswa';
    case gurubk = 'gurubk';
    case wakakesiswaan = 'wakakesiswaan';
    case alumni = 'alumni';
    case kepalasekolah = 'kepalasekolah';
}