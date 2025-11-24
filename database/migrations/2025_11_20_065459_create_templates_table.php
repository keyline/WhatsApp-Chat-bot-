<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('template_name'); // e.g. welcome_new_user

            $table->enum('category', ['marketing', 'utility', 'authentication'])
                ->default('marketing');

            $table->string('language', 10)->default('en'); // en, hi, etc.

            $table->text('header')->nullable();
            $table->text('body');
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable(); // CTA, quick replies etc.

            $table->enum('status', ['approved', 'rejected', 'pending'])
                ->default('pending');

            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // avoid duplicate template per user + language
            $table->unique(['user_id', 'template_name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
