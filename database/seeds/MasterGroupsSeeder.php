<?php

use Illuminate\Database\Seeder;
use App\Utilities\CSV;
use App\Http\Controllers\DataController;

class MasterGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $defaultMasterGroups = [
          [
            'groupKey' => '$Country',
            'groupName' => 'ประเทศ',
            'description' => 'รายการประเทศ',
            'propertiesTemplate' => '<v-text-field v-model="data.abbrCode" label="Abbreviation Code"></v-text-field>',
          ],
          [
            'groupKey' => '$Province',
            'groupName' => 'จังหวัด',
            'description' => 'รายการจังหวัด',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$Subdistrict',
            'groupName' => 'ตำบล/แขวง',
            'description' => 'ตำบล/แขวง',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$District',
            'groupName' => 'อำเภอ/เขต',
            'description' => 'อำเภอ/เขต',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$NamePrefix',
            'groupName' => 'คำนำหน้าชื่อ',
            'description' => 'คำนำหน้าชื่อ สำหรับชื่อผู้ป่วย',
            'propertiesTemplate' =>  '<v-text-field v-model="data.abbr" label="Abbreviation"></v-text-field>',
          ],
          [
            'groupKey' => '$Occupation',
            'groupName' => 'อาชีพ',
            'description' => 'รายการอาชีพ',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$MaritalStatus',
            'groupName' => 'สถานะภาพสมรส',
            'description' => 'รายการสถานะภาพสมรส',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$Nationality',
            'groupName' => 'สัญชาติ',
            'description' => 'รายการสัญชาติ',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$PayerType',
            'groupName' => 'ชนิดผู้ชำระค่าใช้จ่าย',
            'description' => 'ชนิดผู้ชำระค่าใช้จ่าย',
            'propertiesTemplate' => null,
          ],
        ];

        

        foreach($defaultMasterGroups as $MasterGroup) {
          if (\App\Models\Master\MasterGroups::find($MasterGroup['groupKey'])==null) {
            DB::table('master_groups')->insert($MasterGroup);
            $filename = ltrim($MasterGroup['groupKey'],'$');
            if (file_exists(storage_path('app/default/'.$filename.'.csv'))) $this->CSVImport($MasterGroup['groupKey'],$filename);
          }
        }
    }

    public function CSVImport($groupKey, $filename) {
      try {
        $Item = CSV::CSVtoArray(storage_path('app/default/'.$filename.'.csv'));
        array_walk($Item, function(&$a) use ($groupKey) {
          $a = array_merge(['groupKey'=>$groupKey], $a);
        });
        DataController::createModel($Item,\App\Models\Master\MasterItems::class);
        print('Successful imported '.$groupKey."\n");
      } catch (\Exception $e) {
        print('Failed imported '.$e->getMessage()."\n");
      }
    }
}
