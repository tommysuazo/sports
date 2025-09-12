<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NflMarketJsonSeeder extends Seeder
{
    /**
     * URLs iniciales para obtener los game IDs
     */
    private $initialUrls = [
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Passing%20Yards&league=nfl',
            'description' => 'All game providers for players passing yards market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Pass%20Completions&league=nfl',
            'description' => 'All game providers for players complete passes market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Passing%20TDs&league=nfl',
            'description' => 'All game providers for players passing touchdowns market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Pass%20Attempts&league=nfl',
            'description' => 'All game providers for players pass attempts market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Rushing%20Yards&league=nfl',
            'description' => 'All game providers for players rushing yards market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Carries&league=nfl',
            'description' => 'All game providers for players carries market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Receiving%20Yards&league=nfl',
            'description' => 'All game providers for players receiving yards market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Receptions&league=nfl',
            'description' => 'All game providers for players receptions market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Pass%20Interceptions&league=nfl',
            'description' => 'All game providers for players interceptions market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Tackles&league=nfl',
            'description' => 'All game providers for players tackles market'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu?gameId=null&statistic=Sacks&league=nfl',
            'description' => 'All game providers for players sacks market'
        ],
    ];

    /**
     * URLs específicas por juego (plantillas)
     */
    private $gameSpecificUrlTemplates = [
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Passing%2520Yards',
            'description' => 'Players passing yards market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Pass%2520Completions',
            'description' => 'Players complete passes market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Passing%2520TDs',
            'description' => 'Players passing touchdowns market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Pass%2520Attempts',
            'description' => 'Players pass attempts market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Rushing%2520Yards',
            'description' => 'Players rushing yards market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Carries',
            'description' => 'Players carries market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Receiving%2520Yards',
            'description' => 'Players receiving yards market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Receptions',
            'description' => 'Players receptions market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Pass%2520Interceptions',
            'description' => 'Players interceptions market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/sgmMarkets/gfm/grouped?sb=juancito&gameId={gameId}',
            'description' => 'Team general markets by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Tackles',
            'description' => 'Players tackles market by game'
        ],
        [
            'url' => 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId={gameId}&statistic=Sacks',
            'description' => 'Players tackles sacks by game'
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== INICIANDO SEEDING DE NFL MARKET DATA ===');
        Log::info('Iniciando seeding de NFL Market data');

        // FASE 1: Obtener datos generales y extraer game IDs (sin almacenar)
        $gameIds = $this->processInitialUrls();
        
        // FASE 2: Procesar datos específicos por juego
        $this->processGameSpecificUrls($gameIds);

        $totalRecords = DB::connection('markets')->table('nfl_markets')->count();
        $this->command->info("=== SEEDING COMPLETADO ===");
        $this->command->info("Total de registros en la base de datos: {$totalRecords}");
        Log::info("Seeding completado. Total de registros: {$totalRecords}");
    }

    /**
     * Procesar URLs iniciales para obtener game IDs (sin almacenar los datos)
     */
    private function processInitialUrls(): array
    {
        $this->command->info("\n--- FASE 1: PROCESANDO URLs INICIALES (SOLO EXTRAYENDO GAME IDs) ---");
        Log::info('Iniciando procesamiento de URLs iniciales');
        
        $allGameIds = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($this->initialUrls as $index => $urlData) {
            $currentNum = $index + 1;
            $totalUrls = count($this->initialUrls);
            
            $this->command->info("🔄 Procesando URL inicial {$currentNum}/{$totalUrls}: {$urlData['description']}");
            Log::info("Procesando URL inicial: {$urlData['url']}");

            try {
                $response = Http::timeout(30)->get($urlData['url']);
                
                if ($response->successful()) {
                    $jsonData = $response->json();
                    
                    // Extraer game IDs de la respuesta
                    $gameIdsFromThisUrl = $this->extractGameIds($jsonData);
                    $allGameIds = array_merge($allGameIds, $gameIdsFromThisUrl);
                    
                    // NO GUARDAMOS los datos iniciales ya que tienen gameId=null
                    
                    $gameCount = count($gameIdsFromThisUrl);
                    $this->command->info("✅ Game IDs extraídos exitosamente: {$gameCount} (datos no almacenados)");
                    Log::info("URL inicial procesada exitosamente", [
                        'url' => $urlData['url'],
                        'game_ids_found' => $gameCount,
                        'game_ids' => $gameIdsFromThisUrl
                    ]);
                    $successCount++;
                    
                } else {
                    $this->command->error("❌ Error HTTP {$response->status()} para URL inicial: {$urlData['url']}");
                    Log::error("Error HTTP en URL inicial", [
                        'url' => $urlData['url'],
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    $errorCount++;
                }
                
            } catch (\Exception $e) {
                $this->command->error("💥 Excepción en URL inicial: {$urlData['url']} - {$e->getMessage()}");
                Log::error("Excepción en URL inicial", [
                    'url' => $urlData['url'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
            
            usleep(500000); // 0.5 segundos entre peticiones
        }

        // Obtener IDs únicos (sin prioridad)
        $uniqueGameIds = array_unique($allGameIds);
        
        $this->command->info("\n📊 RESUMEN FASE 1:");
        $this->command->info("URLs exitosas: {$successCount}");
        $this->command->info("URLs con error: {$errorCount}");
        $this->command->info("Game IDs únicos encontrados: " . count($uniqueGameIds));
        $this->command->info("Game IDs: " . implode(', ', array_slice($uniqueGameIds, 0, 10)) . (count($uniqueGameIds) > 10 ? '...' : ''));
        
        Log::info("Fase 1 completada", [
            'successful_urls' => $successCount,
            'failed_urls' => $errorCount,
            'unique_game_ids' => count($uniqueGameIds),
            'game_ids' => $uniqueGameIds
        ]);

        return $uniqueGameIds;
    }

    /**
     * Procesar URLs específicas por cada game ID
     */
    private function processGameSpecificUrls(array $gameIds): void
    {
        $this->command->info("\n--- FASE 2: PROCESANDO URLs ESPECÍFICAS POR JUEGO ---");
        Log::info('Iniciando procesamiento de URLs específicas por juego');

        $totalGames = count($gameIds);
        $totalUrlsPerGame = count($this->gameSpecificUrlTemplates);
        $totalExpectedRequests = $totalGames * $totalUrlsPerGame;
        
        $this->command->info("🎯 Juegos a procesar: {$totalGames}");
        $this->command->info("🔗 URLs por juego: {$totalUrlsPerGame}");
        $this->command->info("📊 Total de peticiones esperadas: {$totalExpectedRequests}");
        
        $globalSuccessCount = 0;
        $globalErrorCount = 0;
        $globalUpdatedCount = 0;
        $globalCreatedCount = 0;
        $globalSkippedCount = 0;
        $gameProcessedCount = 0;

        foreach ($gameIds as $gameId) {
            $gameProcessedCount++;
            
            $this->command->info("\n🎮 Procesando juego {$gameProcessedCount}/{$totalGames}: Game ID {$gameId}");
            Log::info("Procesando juego específico", ['game_id' => $gameId]);

            $gameSuccessCount = 0;
            $gameErrorCount = 0;
            $gameUpdatedCount = 0;
            $gameCreatedCount = 0;
            $gameSkippedCount = 0;

            foreach ($this->gameSpecificUrlTemplates as $index => $template) {
                $currentUrlNum = $index + 1;
                $url = str_replace('{gameId}', $gameId, $template['url']);
                
                $this->command->info("  🔄 URL {$currentUrlNum}/{$totalUrlsPerGame}: {$template['description']}");
                
                try {
                    $response = Http::timeout(30)->get($url);
                    
                    if ($response->successful()) {
                        $jsonData = $response->json();
                        
                        // Verificar si el JSON está vacío
                        if (empty($jsonData)) {
                            $this->command->info("    ⏭️ Registro omitido (respuesta JSON vacía)");
                            Log::info("Registro omitido por respuesta vacía", [
                                'game_id' => $gameId,
                                'url' => $url,
                                'description' => $template['description']
                            ]);
                            $gameSkippedCount++;
                            $globalSkippedCount++;
                            continue;
                        }
                        
                        // Verificar si ya existe un registro con esta URL
                        $existingRecord = DB::connection('markets')
                            ->table('nfl_markets')
                            ->where('url', $url)
                            ->first();
                        
                        if ($existingRecord) {
                            // Actualizar registro existente
                            DB::connection('markets')
                                ->table('nfl_markets')
                                ->where('url', $url)
                                ->update([
                                    'body' => json_encode($jsonData),
                                    'updated_at' => now(),
                                ]);
                            
                            $this->command->info("    🔄 Registro actualizado (URL ya existía)");
                            Log::info("Registro actualizado", [
                                'game_id' => $gameId,
                                'url' => $url,
                                'description' => $template['description']
                            ]);
                            $gameUpdatedCount++;
                            $globalUpdatedCount++;
                        } else {
                            // Crear nuevo registro
                            DB::connection('markets')
                                ->table('nfl_markets')
                                ->insert([
                                    'game_id' => $gameId,
                                    'url' => $url,
                                    'body' => json_encode($jsonData),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            
                            $this->command->info("    ✅ Nuevo registro creado");
                            Log::info("Nuevo registro creado", [
                                'game_id' => $gameId,
                                'url' => $url,
                                'description' => $template['description']
                            ]);
                            $gameCreatedCount++;
                            $globalCreatedCount++;
                        }
                        
                        $gameSuccessCount++;
                        $globalSuccessCount++;
                        
                    } else {
                        $this->command->error("    ❌ Error HTTP {$response->status()}");
                        Log::error("Error HTTP en URL específica", [
                            'game_id' => $gameId,
                            'url' => $url,
                            'status' => $response->status(),
                            'response_preview' => substr($response->body(), 0, 200)
                        ]);
                        $gameErrorCount++;
                        $globalErrorCount++;
                    }
                    
                } catch (\Exception $e) {
                    $this->command->error("    💥 Excepción: {$e->getMessage()}");
                    Log::error("Excepción en URL específica", [
                        'game_id' => $gameId,
                        'url' => $url,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $gameErrorCount++;
                    $globalErrorCount++;
                }
                
                usleep(300000); // 0.3 segundos entre peticiones del mismo juego
            }

            $this->command->info("  📊 Juego {$gameId} completado - Exitosas: {$gameSuccessCount}, Errores: {$gameErrorCount}, Omitidas: {$gameSkippedCount}");
            $this->command->info("      📝 Creados: {$gameCreatedCount}, Actualizados: {$gameUpdatedCount}");
            Log::info("Juego completado", [
                'game_id' => $gameId,
                'successful_urls' => $gameSuccessCount,
                'failed_urls' => $gameErrorCount,
                'created_records' => $gameCreatedCount,
                'updated_records' => $gameUpdatedCount,
                'skipped_records' => $gameSkippedCount
            ]);

            usleep(1000000); // 1 segundo entre juegos
        }

        $this->command->info("\n📊 RESUMEN FASE 2:");
        $this->command->info("URLs exitosas: {$globalSuccessCount}");
        $this->command->info("URLs con error: {$globalErrorCount}");
        $this->command->info("URLs omitidas (JSON vacío): {$globalSkippedCount}");
        $this->command->info("Registros creados: {$globalCreatedCount}");
        $this->command->info("Registros actualizados: {$globalUpdatedCount}");
        $this->command->info("Juegos procesados: {$gameProcessedCount}");
        
        Log::info("Fase 2 completada", [
            'successful_urls' => $globalSuccessCount,
            'failed_urls' => $globalErrorCount,
            'skipped_urls' => $globalSkippedCount,
            'created_records' => $globalCreatedCount,
            'updated_records' => $globalUpdatedCount,
            'games_processed' => $gameProcessedCount
        ]);
    }

    /**
     * Extraer game IDs de la respuesta JSON
     */
    private function extractGameIds(array $jsonData): array
    {
        $gameIds = [];
        
        if (is_array($jsonData)) {
            foreach ($jsonData as $game) {
                if (isset($game['providers']) && is_array($game['providers'])) {
                    foreach ($game['providers'] as $provider) {
                        if (isset($provider['id'])) {
                            $gameIds[] = (string) $provider['id'];
                        }
                    }
                }
            }
        }
        
        return $gameIds;
    }
}