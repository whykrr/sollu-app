<?php

namespace App\Controllers;

use Throwable;
use App\Models\SettingModel;
use App\Controllers\BaseController;

class Setting extends BaseController
{
    public function index()
    {
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
        exec('cd ' . ROOTPATH . '; git pull', $result);

        // check dir update-log exist or not
        if (!file_exists(ROOTPATH . 'update-log')) {
            mkdir(ROOTPATH . 'update-log', 0777, true);
        }

        $date_update = date('ymdhis');
        // create file on update directory
        $file = fopen(ROOTPATH . 'update-log/update-' . $date_update . '.txt', 'w');
        fwrite($file,  "Update on " . date('Y-m-d H:i:s') . PHP_EOL);
        fwrite($file,  "-- Pulling file --" . date('Y-m-d H:i:s') . PHP_EOL);
        foreach ($result as $line) {
            fwrite($file, $line . PHP_EOL);
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

        fclose($file);

        return redirect()->to('/setting');
    }

    private function deleteAll($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->deleteAll($file);
            } else {
                echo $file;
                unlink($file);
            }
        }
    }
}
