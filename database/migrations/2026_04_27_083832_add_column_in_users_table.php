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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('department_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('office_location_id')->nullable()->after('department_id');
            $table->string('employee_id', 150)->nullable()->after('office_location_id');
            $table->renameColumn('name', 'full_name');
            $table->string('phone', 15)->nullable()->after('email');
            $table->text('photo')->nullable()->after('phone');
            $table->text('face_embedding')->nullable()->after('photo');
            $table->enum('role', ['employee', 'manager', 'hr', 'admin'])->default('employee')->after('face_embedding');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('role');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('office_location_id')->references('id')->on('office_locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
