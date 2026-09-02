<?php

use App\Services\ThemeService;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Agent\AgentPanelController;
use App\Http\Controllers\Agent\MasterAgentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('agent')->group(function () {
    Route::get('/', [AgentPanelController::class, 'dashboard']);
    Route::get('/login', [AgentPanelController::class, 'loginPage']);
    Route::post('/login', [AgentPanelController::class, 'login']);
    Route::get('/logout', [AgentPanelController::class, 'logout']);
    Route::get('/dashboard', [AgentPanelController::class, 'dashboard']);
    Route::get('/users', [AgentPanelController::class, 'users']);
    Route::get('/orders', [AgentPanelController::class, 'orders']);
    Route::get('/commissions', [AgentPanelController::class, 'commissions']);
    Route::get('/settings', [AgentPanelController::class, 'settings']);
    Route::post('/settings', [AgentPanelController::class, 'updateSettings']);

    Route::get('/master/login', [MasterAgentController::class, 'loginPage']);
    Route::post('/master/login', [MasterAgentController::class, 'login']);
    Route::get('/master/logout', [MasterAgentController::class, 'logout']);
    Route::get('/master', [MasterAgentController::class, 'index']);
    Route::post('/master/agents', [MasterAgentController::class, 'store']);
    Route::post('/master/agents/{agent}', [MasterAgentController::class, 'update']);
    Route::get('/master/commissions', [MasterAgentController::class, 'commissions']);
    Route::post('/master/commissions/settle', [MasterAgentController::class, 'settle']);
});


Route::get('/', function (Request $request) {
    $agent = app(\App\Services\AgentService::class)->resolveFromRequest($request);
    if (admin_setting('app_url') && admin_setting('safe_mode_enable', 0)) {
        $requestHost = $request->getHost();
        $configHost = parse_url(admin_setting('app_url'), PHP_URL_HOST);
        
        if ($requestHost !== $configHost && !$agent) {
            abort(403);
        }
    }

    $theme = admin_setting('frontend_theme', 'Xboard');
    $themeService = new ThemeService();

    try {
        if (!$themeService->exists($theme)) {
            if ($theme !== 'Xboard') {
                Log::warning('Theme not found, switching to default theme', ['theme' => $theme]);
                $theme = 'Xboard';
                admin_setting(['frontend_theme' => $theme]);
            }
            $themeService->switch($theme);
        }

        if (!$themeService->getThemeViewPath($theme)) {
            throw new Exception('主题视图文件不存在');
        }

        $publicThemePath = public_path('theme/' . $theme);
        if (!File::exists($publicThemePath)) {
            $themePath = $themeService->getThemePath($theme);
            if (!$themePath || !File::copyDirectory($themePath, $publicThemePath)) {
                throw new Exception('主题初始化失败');
            }
            Log::info('Theme initialized in public directory', ['theme' => $theme]);
        }

        $renderParams = [
            'title' => $agent?->site_name ?: admin_setting('app_name', 'Xboard'),
            'theme' => $theme,
            'version' => app(UpdateService::class)->getCurrentVersion(),
            'description' => admin_setting('app_description', 'Xboard is best'),
            'logo' => $agent?->logo ?: admin_setting('logo'),
            'theme_config' => $themeService->getConfig($theme)
        ];
        $response = response()->view('theme::' . $theme . '.dashboard', $renderParams);
        if ($agent) {
            $response->withCookie(cookie('xboard_agent_code', $agent->code, 60 * 24 * 30, null, null, false, false));
        }
        return $response;
    } catch (Exception $e) {
        Log::error('Theme rendering failed', [
            'theme' => $theme,
            'error' => $e->getMessage()
        ]);
        abort(500, '主题加载失败');
    }
});

//TODO:: 兼容
Route::get('/' . admin_setting('secure_path', admin_setting('frontend_admin_path', hash('crc32b', config('app.key')))), function () {
    return view('admin', [
        'title' => admin_setting('app_name', 'XBoard'),
        'theme_sidebar' => admin_setting('frontend_theme_sidebar', 'light'),
        'theme_header' => admin_setting('frontend_theme_header', 'dark'),
        'theme_color' => admin_setting('frontend_theme_color', 'default'),
        'background_url' => admin_setting('frontend_background_url'),
        'version' => app(UpdateService::class)->getCurrentVersion(),
        'logo' => admin_setting('logo'),
        'secure_path' => admin_setting('secure_path', admin_setting('frontend_admin_path', hash('crc32b', config('app.key'))))
    ]);
});

Route::get('/' . (admin_setting('subscribe_path', 's')) . '/{token}', [\App\Http\Controllers\V1\Client\ClientController::class, 'subscribe'])
    ->middleware('client')
    ->name('client.subscribe');
