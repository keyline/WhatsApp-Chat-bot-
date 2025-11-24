<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('business_account_id')->nullable(); // WABA ID
            $table->string('phone_number_id')->nullable();
            $table->string('whatsapp_number')->nullable();

            $table->text('access_token')->nullable();
            $table->text('app_secret')->nullable();
            $table->string('verify_token')->nullable();
            $table->string('webhook_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
