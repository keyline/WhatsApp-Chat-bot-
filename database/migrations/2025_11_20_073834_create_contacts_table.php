<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();

            $table->json('tags')->nullable(); // ["premium","real-estate"]

            $table->enum('optin_status', ['opted_in', 'pending', 'opted_out'])
                ->default('pending');

            $table->text('last_message')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            // same phone can exist for different users, but unique per user
            $table->unique(['user_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
