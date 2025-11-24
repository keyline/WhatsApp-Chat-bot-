<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_targets', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->enum('send_status', ['pending', 'sent', 'delivered', 'read', 'failed'])
                ->default('pending');

            $table->string('message_id')->nullable(); // Meta message id
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->unique(['campaign_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_targets');
    }
};
