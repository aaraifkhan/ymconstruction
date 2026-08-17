<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_sales_report_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_work_report_id')->unique()->constrained('daily_work_reports')->cascadeOnDelete();
            $table->unsignedInteger('leads_received_count')->default(0);
            $table->unsignedInteger('leads_contacted_count')->default(0);
            $table->unsignedInteger('calls_made_count')->default(0);
            $table->unsignedInteger('messages_sent_count')->default(0);
            $table->unsignedInteger('followups_done_count')->default(0);
            $table->unsignedInteger('meetings_booked_count')->default(0);
            $table->unsignedInteger('proposals_sent_count')->default(0);
            $table->unsignedInteger('deals_closed_count')->default(0);
            $table->decimal('revenue_generated', 19, 4)->default(0);
            $table->unsignedInteger('pending_leads_count')->default(0);
            $table->unsignedInteger('lost_leads_count')->default(0);
            $table->text('lost_lead_reasons')->nullable();
            $table->text('next_followup_targets')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales_report_details');
    }
};
