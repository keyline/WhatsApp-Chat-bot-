<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->enum('type', ['broadcast', 'automation', 'bot'])
                ->default('broadcast');

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('templates')
                ->nullOnDelete();

            $table->enum('status', ['scheduled', 'running', 'paused', 'completed'])
                ->default('scheduled');

            $table->timestamp('scheduled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
