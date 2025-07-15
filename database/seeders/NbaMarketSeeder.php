<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NbaMarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statements = [
        "UPDATE nba_players
		SET points_market = 10.5,
		assists_market = 2.5,
		rebounds_market = 8.5,
		pt3_market = 0.5,
		pra_market = 21.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 75;",
	
	
		"UPDATE nba_players
		SET points_market = 12.5,
		assists_market = null,
		rebounds_market = 5.5,
		pt3_market = 2.5,
		pra_market = 19.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 79;",
	
	
		"UPDATE nba_players
		SET points_market = 16.5,
		assists_market = 6.5,
		rebounds_market = 2.5,
		pt3_market = 2.5,
		pra_market = 25.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 85;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = 2.5,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = 19.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 90;",
	
	
		"UPDATE nba_players
		SET points_market = 18.5,
		assists_market = null,
		rebounds_market = 7.5,
		pt3_market = 2.5,
		pra_market = 27.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 103;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = 3.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 20.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 104;",
	
	
		"UPDATE nba_players
		SET points_market = 21.5,
		assists_market = 4.5,
		rebounds_market = 5.5,
		pt3_market = 1.5,
		pra_market = 31.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 108;",
	
	
		"UPDATE nba_players
		SET points_market = 25.5,
		assists_market = 5.5,
		rebounds_market = 8.5,
		pt3_market = 3.5,
		pra_market = 39.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 109;",
	
	
		"UPDATE nba_players
		SET points_market = 10.5,
		assists_market = 3.5,
		rebounds_market = 4.5,
		pt3_market = 1.5,
		pra_market = 18.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 111;",
	
	
		"UPDATE nba_players
		SET points_market = 22.5,
		assists_market = 4.5,
		rebounds_market = 7.5,
		pt3_market = 1.5,
		pra_market = 35.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 126;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 132;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = 2.5,
		rebounds_market = 10.5,
		pt3_market = null,
		pra_market = 26.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 137;",
	
	
		"UPDATE nba_players
		SET points_market = 21.5,
		assists_market = 6.5,
		rebounds_market = 8.5,
		pt3_market = 1.5,
		pra_market = 36.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 139;",
	
	
		"UPDATE nba_players
		SET points_market = 6.5,
		assists_market = 5.5,
		rebounds_market = null,
		pt3_market = null,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 140;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = 7.5,
		rebounds_market = 7.5,
		pt3_market = 1.5,
		pra_market = 28.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 150;",
	
	
		"UPDATE nba_players
		SET points_market = 20.5,
		assists_market = 3.5,
		rebounds_market = 10.5,
		pt3_market = 1.5,
		pra_market = 33.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 152;",
	
	
		"UPDATE nba_players
		SET points_market = 9.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 158;",
	
	
		"UPDATE nba_players
		SET points_market = 9.5,
		assists_market = 3.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 16.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 161;",
	
	
		"UPDATE nba_players
		SET points_market = 26.5,
		assists_market = 4.5,
		rebounds_market = 4.5,
		pt3_market = 2.5,
		pra_market = 35.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 166;",
	
	
		"UPDATE nba_players
		SET points_market = 26.5,
		assists_market = 4.5,
		rebounds_market = 4.5,
		pt3_market = 3.5,
		pra_market = 35.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 172;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = null,
		rebounds_market = 10.5,
		pt3_market = null,
		pra_market = 24.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 182;",
	
	
		"UPDATE nba_players
		SET points_market = 17.5,
		assists_market = 2.5,
		rebounds_market = 9.5,
		pt3_market = 1.5,
		pra_market = 29.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 184;",
	
	
		"UPDATE nba_players
		SET points_market = 21.5,
		assists_market = 6.5,
		rebounds_market = 2.5,
		pt3_market = 2.5,
		pra_market = 31.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 186;",
	
	
		"UPDATE nba_players
		SET points_market = 8.5,
		assists_market = 3.5,
		rebounds_market = 4.5,
		pt3_market = 1.5,
		pra_market = 16.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 188;",
	
	
		"UPDATE nba_players
		SET points_market = 12.5,
		assists_market = 2.5,
		rebounds_market = 5.5,
		pt3_market = 1.5,
		pra_market = 19.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 218;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = null,
		rebounds_market = 2.5,
		pt3_market = 2.5,
		pra_market = 15.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 227;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = 2.5,
		rebounds_market = 10.5,
		pt3_market = null,
		pra_market = 24.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 229;",
	
	
		"UPDATE nba_players
		SET points_market = 9.5,
		assists_market = 2.5,
		rebounds_market = 5.5,
		pt3_market = 0.5,
		pra_market = 17.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 230;",
	
	
		"UPDATE nba_players
		SET points_market = 27.5,
		assists_market = 9.5,
		rebounds_market = 5.5,
		pt3_market = 2.5,
		pra_market = 42.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 237;",
	
	
		"UPDATE nba_players
		SET points_market = 20.5,
		assists_market = 4.5,
		rebounds_market = 8.5,
		pt3_market = 1.5,
		pra_market = 33.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 266;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = null,
		rebounds_market = 11.5,
		pt3_market = null,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 275;",
	
	
		"UPDATE nba_players
		SET points_market = 8.5,
		assists_market = 4.5,
		rebounds_market = null,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 276;",
	
	
		"UPDATE nba_players
		SET points_market = 12.5,
		assists_market = null,
		rebounds_market = 6.5,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 281;",
	
	
		"UPDATE nba_players
		SET points_market = 6.5,
		assists_market = 2.5,
		rebounds_market = null,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 282;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = null,
		rebounds_market = 6.5,
		pt3_market = 1.5,
		pra_market = 21.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 283;",
	
	
		"UPDATE nba_players
		SET points_market = 28.5,
		assists_market = 5.5,
		rebounds_market = 6.5,
		pt3_market = 3.5,
		pra_market = 40.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 286;",
	
	
		"UPDATE nba_players
		SET points_market = 12.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = null,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 362;",
	
	
		"UPDATE nba_players
		SET points_market = 15.5,
		assists_market = null,
		rebounds_market = 5.5,
		pt3_market = 2.5,
		pra_market = 23.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 367;",
	
	
		"UPDATE nba_players
		SET points_market = 20.5,
		assists_market = 6.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 30.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 368;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = 5.5,
		rebounds_market = 5.5,
		pt3_market = 1.5,
		pra_market = 25.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 369;",
	
	
		"UPDATE nba_players
		SET points_market = 28.5,
		assists_market = 10.5,
		rebounds_market = 13.5,
		pt3_market = 1.5,
		pra_market = 52.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 380;",
	
	
		"UPDATE nba_players
		SET points_market = 20.5,
		assists_market = 2.5,
		rebounds_market = 4.5,
		pt3_market = 2.5,
		pra_market = 27.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 414;",
	
	
		"UPDATE nba_players
		SET points_market = 10.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = 15.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 418;",
	
	
		"UPDATE nba_players
		SET points_market = 20.5,
		assists_market = 4.5,
		rebounds_market = 9.5,
		pt3_market = null,
		pra_market = 33.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 424;",
	
	
		"UPDATE nba_players
		SET points_market = 16.5,
		assists_market = 3.5,
		rebounds_market = 9.5,
		pt3_market = null,
		pra_market = 29.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 425;",
	
	
		"UPDATE nba_players
		SET points_market = 14.5,
		assists_market = 5.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 24.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 428;",
	
	
		"UPDATE nba_players
		SET points_market = 21.5,
		assists_market = 4.5,
		rebounds_market = 6.5,
		pt3_market = null,
		pra_market = 33.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 441;",
	
	
		"UPDATE nba_players
		SET points_market = 20.5,
		assists_market = 3.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 27.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 447;",
	
	
		"UPDATE nba_players
		SET points_market = 17.5,
		assists_market = 6.5,
		rebounds_market = 5.5,
		pt3_market = 2.5,
		pra_market = 30.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 455;",
	
	
		"UPDATE nba_players
		SET points_market = 8.5,
		assists_market = null,
		rebounds_market = 8.5,
		pt3_market = null,
		pra_market = 18.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 456;",
	
	
		"UPDATE nba_players
		SET points_market = 19.5,
		assists_market = 3.5,
		rebounds_market = 5.5,
		pt3_market = 2.5,
		pra_market = 28.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 457;",
	
	
		"UPDATE nba_players
		SET points_market = 15.5,
		assists_market = 2.5,
		rebounds_market = 10.5,
		pt3_market = null,
		pra_market = 27.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 497;",
	
	
		"UPDATE nba_players
		SET points_market = 16.5,
		assists_market = 2.5,
		rebounds_market = 9.5,
		pt3_market = 1.5,
		pra_market = 28.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 499;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = 2.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 17.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 505;",
	
	
		"UPDATE nba_players
		SET points_market = 15.5,
		assists_market = 4.5,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = 23.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 506;",
	
	
		"UPDATE nba_players
		SET points_market = 26.5,
		assists_market = 5.5,
		rebounds_market = 4.5,
		pt3_market = 2.5,
		pra_market = 37.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 508;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = 3.5,
		rebounds_market = 4.5,
		pt3_market = 1.5,
		pra_market = 20.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 510;",
	
	
		"UPDATE nba_players
		SET points_market = 19.5,
		assists_market = 6.5,
		rebounds_market = 4.5,
		pt3_market = 2.5,
		pra_market = 29.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 537;",
	
	
		"UPDATE nba_players
		SET points_market = 23.5,
		assists_market = 8.5,
		rebounds_market = 7.5,
		pt3_market = 1.5,
		pra_market = 39.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 540;",
	
	
		"UPDATE nba_players
		SET points_market = 10.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 548;",
	
	
		"UPDATE nba_players
		SET points_market = 25.5,
		assists_market = 3.5,
		rebounds_market = 12.5,
		pt3_market = null,
		pra_market = 41.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 549;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = null,
		rebounds_market = 4.5,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 551;",
	
	
		"UPDATE nba_players
		SET points_market = 8.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = null,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 558;",
	
	
		"UPDATE nba_players
		SET points_market = 21.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 26.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 563;",
	
	
		"UPDATE nba_players
		SET points_market = 8.5,
		assists_market = null,
		rebounds_market = 2.5,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 565;",
	
	
		"UPDATE nba_players
		SET points_market = 8.5,
		assists_market = 3.5,
		rebounds_market = 2.5,
		pt3_market = null,
		pra_market = 14.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 566;",
	
	
		"UPDATE nba_players
		SET points_market = 12.5,
		assists_market = null,
		rebounds_market = 12.5,
		pt3_market = null,
		pra_market = 27.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 567;",
	
	
		"UPDATE nba_players
		SET points_market = 21.5,
		assists_market = 9.5,
		rebounds_market = 4.5,
		pt3_market = 2.5,
		pra_market = 35.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 571;",
	
	
		"UPDATE nba_players
		SET points_market = 16.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = 21.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 573;",
	
	
		"UPDATE nba_players
		SET points_market = 25.5,
		assists_market = 4.5,
		rebounds_market = 6.5,
		pt3_market = 2.5,
		pra_market = 36.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 589;",
	
	
		"UPDATE nba_players
		SET points_market = 9.5,
		assists_market = 4.5,
		rebounds_market = null,
		pt3_market = 1.5,
		pra_market = 16.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 591;",
	
	
		"UPDATE nba_players
		SET points_market = 24.5,
		assists_market = 6.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 35.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 595;",
	
	
		"UPDATE nba_players
		SET points_market = 14.5,
		assists_market = 3.5,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = 22.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 600;",
	
	
		"UPDATE nba_players
		SET points_market = 19.5,
		assists_market = 5.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 29.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 608;",
	
	
		"UPDATE nba_players
		SET points_market = 22.5,
		assists_market = 5.5,
		rebounds_market = 4.5,
		pt3_market = 1.5,
		pra_market = 32.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 609;",
	
	
		"UPDATE nba_players
		SET points_market = 19.5,
		assists_market = 6.5,
		rebounds_market = 15.5,
		pt3_market = 0.5,
		pra_market = 42.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 618;",
	
	
		"UPDATE nba_players
		SET points_market = 20.5,
		assists_market = 2.5,
		rebounds_market = 3.5,
		pt3_market = 0.5,
		pra_market = 27.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 621;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = null,
		rebounds_market = 6.5,
		pt3_market = 2.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 623;",
	
	
		"UPDATE nba_players
		SET points_market = 12.5,
		assists_market = 3.5,
		rebounds_market = 4.5,
		pt3_market = 1.5,
		pra_market = 20.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 635;",
	
	
		"UPDATE nba_players
		SET points_market = 22.5,
		assists_market = 5.5,
		rebounds_market = 3.5,
		pt3_market = 3.5,
		pra_market = 31.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 637;",
	
	
		"UPDATE nba_players
		SET points_market = 12.5,
		assists_market = 2.5,
		rebounds_market = 8.5,
		pt3_market = 1.5,
		pra_market = 23.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 641;",
	
	
		"UPDATE nba_players
		SET points_market = 16.5,
		assists_market = 3.5,
		rebounds_market = 6.5,
		pt3_market = 1.5,
		pra_market = 26.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 644;",
	
	
		"UPDATE nba_players
		SET points_market = 10.5,
		assists_market = null,
		rebounds_market = 7.5,
		pt3_market = null,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 648;",
	
	
		"UPDATE nba_players
		SET points_market = 18.5,
		assists_market = 3.5,
		rebounds_market = 7.5,
		pt3_market = 2.5,
		pra_market = 30.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 665;",
	
	
		"UPDATE nba_players
		SET points_market = 27.5,
		assists_market = 7.5,
		rebounds_market = 5.5,
		pt3_market = 3.5,
		pra_market = 41.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 666;",
	
	
		"UPDATE nba_players
		SET points_market = 16.5,
		assists_market = 2.5,
		rebounds_market = 11.5,
		pt3_market = null,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 675;",
	
	
		"UPDATE nba_players
		SET points_market = 11.5,
		assists_market = 2.5,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 17.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 676;",
	
	
		"UPDATE nba_players
		SET points_market = 7.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 677;",
	
	
		"UPDATE nba_players
		SET points_market = 10.5,
		assists_market = null,
		rebounds_market = 2.5,
		pt3_market = 1.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 689;",
	
	
		"UPDATE nba_players
		SET points_market = 15.5,
		assists_market = 5.5,
		rebounds_market = 5.5,
		pt3_market = 1.5,
		pra_market = 26.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 697;",
	
	
		"UPDATE nba_players
		SET points_market = 14.5,
		assists_market = 2.5,
		rebounds_market = 8.5,
		pt3_market = null,
		pra_market = 25.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 698;",
	
	
		"UPDATE nba_players
		SET points_market = 6.5,
		assists_market = null,
		rebounds_market = 6.5,
		pt3_market = null,
		pra_market = 14.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 701;",
	
	
		"UPDATE nba_players
		SET points_market = 9.5,
		assists_market = 4.5,
		rebounds_market = 2.5,
		pt3_market = 1.5,
		pra_market = 17.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 706;",
	
	
		"UPDATE nba_players
		SET points_market = 13.5,
		assists_market = 2.5,
		rebounds_market = null,
		pt3_market = 2.5,
		pra_market = null,
		steals_market = null,
		blocks_market = null
		WHERE id = 707;",
	

		"UPDATE nba_players
		SET points_market = 19.5,
		assists_market = null,
		rebounds_market = 3.5,
		pt3_market = 2.5,
		pra_market = 24.5,
		steals_market = null,
		blocks_market = null
		WHERE id = 708;",
        ];
        
        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }
}
