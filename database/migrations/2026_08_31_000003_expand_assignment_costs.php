<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assignments')->orderBy('id')->each(function (object $assignment): void {
            $costs = json_decode($assignment->costs, true);
            $turnCost = $costs['draaien'] ?? 5;
            $costs = array_replace(config('glade.default_costs'), $costs, [
                'draaiLinks' => $costs['draaiLinks'] ?? $turnCost,
                'draaiRechts' => $costs['draaiRechts'] ?? $turnCost,
                'kompasVerbruik' => $costs['kompasVerbruik'] ?? 15,
            ]);
            unset($costs['draaien']);

            DB::table('assignments')->where('id', $assignment->id)->update(['costs' => json_encode($costs)]);
        });
    }

    public function down(): void
    {
        DB::table('assignments')->orderBy('id')->each(function (object $assignment): void {
            $costs = json_decode($assignment->costs, true);
            $costs['draaien'] = $costs['draaiLinks'] ?? 5;
            unset($costs['draaiLinks'], $costs['draaiRechts'], $costs['kompasVerbruik']);

            DB::table('assignments')->where('id', $assignment->id)->update(['costs' => json_encode($costs)]);
        });
    }
};
