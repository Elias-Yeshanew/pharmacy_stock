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
        Schema::table('medicines', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('expiry_date');
            $table->index(['is_active', 'expiry_date']);
            $table->index('name');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('status');
            $table->index(['branch_id', 'status', 'created_at']);
            $table->index('customer_name');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('type');
            $table->index(['medicine_id', 'branch_id']);
        });

        Schema::table('medicine_branch', function (Blueprint $table) {
            $table->index(['branch_id', 'stock_quantity']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('order_date');
            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['expiry_date']);
            $table->dropIndex(['is_active', 'expiry_date']);
            $table->dropIndex(['name']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);
            $table->dropIndex(['branch_id', 'status', 'created_at']);
            $table->dropIndex(['customer_name']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['type']);
            $table->dropIndex(['medicine_id', 'branch_id']);
        });

        Schema::table('medicine_branch', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'stock_quantity']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['order_date']);
            $table->dropIndex(['branch_id', 'status']);
        });
    }
};
