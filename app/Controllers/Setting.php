<?php

namespace App\Controllers;

use Throwable;
use App\Models\SettingModel;
use App\Controllers\BaseController;

class Setting extends BaseController
{
    public function index()
    {
        // reset all cache

        $sesion = session();

        // hard load if update success
        if ($sesion->getFlashdata('update-success')) {
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        }

        $setting = new SettingModel();

        $data['sidebar_active'] = 'setting';
        $data['data'] = $setting->findAll();
        $data['category'] = [];

        // group category setting
        foreach ($data['data'] as $key => $value) {
            // check category if not in array
            if (!in_array($value['category'], $data['category'])) {
                // push to array
                array_push($data['category'], $value['category']);
            }
        }

        return view('setting/index', $data);
    }

    /**
     * Save setting
     */
    public function save()
    {
        $setting = new SettingModel();

        $data = $this->request->getPost();
        $saveData = [];

        foreach ($data['id'] as $key => $value) {
            $saveData[] = [
                'id' => $value,
                'value' => $data['value'][$key]
            ];
        }

        $setting->updateBatch($saveData, 'id');

        return redirect()->to('/setting');
    }

    public function update()
    {
        $rootPath = ROOTPATH;
        $fcPath = ROOTPATH;

        // check if os windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            echo $rootPath = str_replace('\\', '/', $rootPath);
            $fcPath = str_replace('\\', '/', $fcPath);
        }


        exec('cd ' . $rootPath . ' && git pull', $result);

        // check if git pull success
        if (strpos($result[0], 'Already up to date.') !== false) {
            return redirect()->to('/setting')->with('update-error', 'Already up to date.');
        }

        // check dir update-log exist or not
        if (!file_exists($rootPath . 'update-log')) {
            mkdir($rootPath . 'update-log', 0777, true);
        }

        // update composer
        exec('cd ' . $rootPath . ' && composer install', $result_composer);

        $date_update = date('ymdhis');
        // create file on update directory
        $file = fopen($rootPath . 'update-log/update-' . $date_update . '.txt', 'w');
        fwrite($file,  "Update on " . date('Y-m-d H:i:s') . PHP_EOL);
        fwrite($file,  "-- Pulling file --" . PHP_EOL);
        foreach ($result as $line) {
            fwrite($file, $line . PHP_EOL);
        }

        fwrite($file,  "-- Composer Install --" . PHP_EOL);
        foreach ($result_composer as $line_c) {
            fwrite($file, $line_c . PHP_EOL);
        }

        $seeder = \Config\Database::seeder();
        $migrate = \Config\Services::migrations();
        $db = \Config\Database::connect();

        try {
            fwrite($file, PHP_EOL . "-- Migrate Executed Migration --" . PHP_EOL);
            $migrate->latest();
            // get data from table migration
            $migrates = $db->table('migrations')->get()->getResultArray();
            foreach ($migrates as $m) {
                fwrite($file, $m['class'] . PHP_EOL);
            }
        } catch (Throwable $e) {
            // throw $th;
            fwrite($file, PHP_EOL . "-- Migrate Error --" . PHP_EOL);
            fwrite($file, $e->getMessage() . PHP_EOL);
        }
        fwrite($file, PHP_EOL . "-- Run DB Seed --" . PHP_EOL);
        fwrite($file, "ResetPermissions" . PHP_EOL);
        $seeder->call('ResetPermissions');

        fwrite($file, PHP_EOL . "-- Copy Asset --" . PHP_EOL);
        $this->copyDirectory($rootPath . 'public/assets', $fcPath . 'assets');
        fwrite($file, "Copy Folder Asset Successfuly" . PHP_EOL);
        $this->copyDirectory($rootPath . 'public/css', FCPATH . 'css');
        fwrite($file, "Copy Folder CSS Successfuly" . PHP_EOL);
        $this->copyDirectory($rootPath . 'public/js', FCPATH . 'js');
        fwrite($file, "Copy Folder JS Successfuly" . PHP_EOL);

        fclose($file);

        //redirect with data
        return redirect()->to('/setting')->with('update-success', 'Update Successfuly');
    }

    // copy and replace all file in directory
    public function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                    continue;
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
