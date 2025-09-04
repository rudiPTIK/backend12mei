<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->string('phone')->nullable()->after('email');
            $t->enum('gender', ['male','female'])->nullable()->after('phone');
            $t->date('birthdate')->nullable()->after('gender');
            $t->string('avatar_path')->nullable()->after('birthdate');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['phone','gender','birthdate','avatar_path']);
        });
    }
};
