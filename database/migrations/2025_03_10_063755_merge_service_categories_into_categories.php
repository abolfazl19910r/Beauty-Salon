<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('categories', 'image')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('image')->nullable()->after('icon');
            });
        }

        $serviceCategories = DB::table('service_categories')->get();

        foreach ($serviceCategories as $serviceCategory) {
            $exists = DB::table('categories')
                ->where('slug', $serviceCategory->slug)
                ->exists();

            if (!$exists) {
                DB::table('categories')->insert([
                    'name' => $serviceCategory->name,
                    'slug' => $serviceCategory->slug,
                    'description' => $serviceCategory->description,
                    'image' => $serviceCategory->image,
                    'is_active' => true,
                    'order' => 0,
                    'created_at' => $serviceCategory->created_at,
                    'updated_at' => $serviceCategory->updated_at
                ]);
            }
        }

        if (Schema::hasTable('beauty_services') && Schema::hasColumn('beauty_services', 'category_id')) {
            Schema::table('beauty_services', function (Blueprint $table) {

                $foreignKeys = DB::select(
                    "SELECT tc.constraint_name
                     FROM information_schema.table_constraints tc
                     JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                     WHERE tc.constraint_type = 'FOREIGN KEY'
                     AND tc.table_name = 'beauty_services'
                     AND kcu.column_name = 'category_id'"
                );

                if (!empty($foreignKeys)) {
                    foreach ($foreignKeys as $foreignKey) {
                        $table->dropForeign($foreignKey->constraint_name);
                    }
                }

                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->onDelete('set null');
            });

            $services = DB::table('beauty_services')
                ->whereNotNull('category_id')
                ->get(['id', 'category_id']);

            foreach ($services as $service) {
                $oldCategory = DB::table('service_categories')
                    ->where('id', $service->category_id)
                    ->first();

                if ($oldCategory) {
                    $newCategory = DB::table('categories')
                        ->where('slug', $oldCategory->slug)
                        ->first();

                    if ($newCategory) {
                        DB::table('beauty_services')
                            ->where('id', $service->id)
                            ->update(['category_id' => $newCategory->id]);
                    }
                }
            }
        }

        Schema::dropIfExists('service_categories');
    }

    public function down()
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

    }
};
