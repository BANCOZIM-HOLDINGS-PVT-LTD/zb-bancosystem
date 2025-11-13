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
        // Form Templates table
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['loan_application', 'customer_onboarding', 'product_inquiry', 'custom'])->default('custom');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->json('form_schema'); // Form structure and fields
            $table->json('validation_rules')->nullable(); // Validation configuration
            $table->json('conditional_logic')->nullable(); // Show/hide field logic
            $table->json('styling_config')->nullable(); // Form appearance settings
            $table->json('notification_config')->nullable(); // Email/SMS notifications
            $table->json('workflow_config')->nullable(); // Approval workflow settings
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'type']);
            $table->index('slug');
        });

        // Form Submissions table
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_template_id');
            $table->string('submission_id')->unique(); // Public reference ID
            $table->json('form_data'); // Submitted form data
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected', 'completed'])->default('pending');
            $table->string('submitter_email')->nullable();
            $table->string('submitter_phone')->nullable();
            $table->string('submitter_name')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable(); // If submitted via agent
            $table->unsignedBigInteger('assigned_to')->nullable(); // Assigned reviewer
            $table->text('review_notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('workflow_state')->nullable(); // Current workflow position
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'submitted_at']);
            $table->index('submission_id');
            $table->index('submitter_email');
        });

        // Form Fields table (for reusable field definitions)
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', [
                'text', 'email', 'phone', 'number', 'textarea', 'select', 'radio', 
                'checkbox', 'date', 'file', 'signature', 'address', 'currency'
            ]);
            $table->text('label');
            $table->text('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->json('options')->nullable(); // For select, radio, checkbox
            $table->json('validation_rules')->nullable();
            $table->json('styling')->nullable();
            $table->boolean('is_system_field')->default(false); // System-defined fields
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['type', 'is_active']);
            $table->index('slug');
        });

        // Document Templates table
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['pdf', 'html', 'docx', 'email'])->default('pdf');
            $table->enum('category', ['contract', 'certificate', 'report', 'notification', 'custom'])->default('custom');
            $table->longText('template_content'); // HTML/template content
            $table->json('merge_fields'); // Available merge fields
            $table->json('styling_config')->nullable(); // PDF styling, fonts, etc.
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('file_path')->nullable(); // For uploaded templates
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'type']);
            $table->index('slug');
        });

        // Generated Documents table
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_template_id');
            $table->unsignedBigInteger('form_submission_id')->nullable();
            $table->string('document_id')->unique(); // Public reference
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size');
            $table->json('merge_data'); // Data used for generation
            $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('generated_by');
            $table->timestamp('generated_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('document_template_id')->references('id')->on('document_templates')->onDelete('cascade');
            $table->foreign('form_submission_id')->references('id')->on('form_submissions')->onDelete('set null');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'generated_at']);
            $table->index('document_id');
        });

        // Form Workflow States table
        Schema::create('form_workflow_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_submission_id');
            $table->string('state_name');
            $table->string('state_type'); // 'review', 'approval', 'notification', 'action'
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped', 'failed'])->default('pending');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->json('state_data')->nullable(); // State-specific data
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('form_submission_id')->references('id')->on('form_submissions')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->index(['form_submission_id', 'sort_order']);
            $table->index(['status', 'assigned_to']);
        });

        // Form Analytics table
        Schema::create('form_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_template_id');
            $table->date('date');
            $table->integer('views')->default(0);
            $table->integer('starts')->default(0);
            $table->integer('submissions')->default(0);
            $table->integer('completions')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0.00);
            $table->decimal('completion_rate', 5, 2)->default(0.00);
            $table->integer('avg_completion_time')->default(0); // in seconds
            $table->json('field_analytics')->nullable(); // Field-specific metrics
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('cascade');
            $table->unique(['form_template_id', 'date']);
            $table->index(['date', 'form_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_analytics');
        Schema::dropIfExists('form_workflow_states');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_templates');
    }
};
