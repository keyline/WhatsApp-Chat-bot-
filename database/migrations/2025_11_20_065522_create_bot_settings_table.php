<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('bot_name');

            $table->enum('trigger_type', ['keyword', 'menu', 'auto_reply'])
                ->default('keyword');

            $table->string('trigger_keyword')->nullable(); // e.g. "hi", "support"

            $table->longText('flow_json'); // full bot flow definition (nodes, edges)
            $table->enum('status', ['active', 'paused'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
