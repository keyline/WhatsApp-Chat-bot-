<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->nullOnDelete(); // null for normal chat/bot messages

            $table->enum('direction', ['IN', 'OUT']); // IN = from user, OUT = from system

            $table->enum('content_type', ['text', 'media', 'button', 'interactive'])
                ->default('text');

            $table->longText('message')->nullable();
            $table->text('media_url')->nullable(); // file url if any

            $table->string('meta_message_id')->nullable(); // wa_id message id

            $table->enum('status', ['sent', 'delivered', 'read', 'failed', 'received'])
                ->default('received'); // for IN messages

            $table->timestamps();

            $table->index(['user_id', 'contact_id']);
            $table->index('meta_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
