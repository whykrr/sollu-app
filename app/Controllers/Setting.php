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
        $seeder = \Config\Database::seeder();
        $migrate = \Config\Services::migrations();

        try {
            $migrate->latest();
        } catch (Throwable $e) {
            // Do something with the error here...
        }
        $seeder->call('ResetPermissions');

        return redirect()->to('/setting');
    }

    public function test_git()
    {
        exec('cd ' . ROOTPATH . '; git pull', $result);
        echo "<pre>";
        foreach ($result as $line) {
            echo $line . "<br>";
        }
        echo "</pre>";
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
        // check directory
        // if (is_dir($dir)) {
        //     rmdir($dir);
        // }
    }
}
