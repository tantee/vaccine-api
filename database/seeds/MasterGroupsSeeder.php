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
            'groupKey' => '$Postcode',
            'groupName' => 'รหัสไปรษณีย์',
            'description' => 'รหัสไปรษณีย์',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$NamePrefix',
            'groupName' => 'คำนำหน้าชื่อ',
            'description' => 'คำนำหน้าชื่อ สำหรับชื่อผู้ป่วย',
            'propertiesTemplate' =>  '<v-text-field v-model="data.abbr" label="Abbreviation"></v-text-field>',
          ],
          [
            'groupKey' => '$NameSuffix',
            'groupName' => 'คำลงท้ายชื่อ',
            'description' => 'คำลงท้ายชื่อ สำหรับชื่อผู้ป่วย',
            'propertiesTemplate' => null,
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
            'groupKey' => '$Unit',
            'groupName' => 'หน่วย',
            'description' => 'หน่วยนับ',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$ProductCategory',
            'groupName' => 'หมวดหมู่สำหรับค้นหา',
            'description' => 'หมวดหมู่สำหรับค้นหา',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$ProductCategoryInsurance',
            'groupName' => 'หมวดหมู่ประกัน',
            'description' => 'หมวดหมู่ตามรายการมาตรฐานประกัน',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$ProductCategoryCgd',
            'groupName' => 'หมวดหมู่กรมบัญชีกลาง',
            'description' => 'หมวดหมู่ตามรายการกรมบัญชีกลาง',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$MedicineRoute',
            'groupName' => 'ทางการบริหารยา',
            'description' => 'ทางการบริหารยา',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$MedicineFrequency',
            'groupName' => 'ความถี่ในการบริหารยา',
            'description' => 'ความถี่ในการบริหารยา',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$MedicineTimes',
            'groupName' => 'จำนวนครั้งในการบริหารยา',
            'description' => 'จำนวนครั้งในการบริหารยา',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$MedicineCaution',
            'groupName' => 'ข้อควรระวังในการบริหารยา',
            'description' => 'ข้อควรระวังในการบริหารยา',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$MedicineMimsClass',
            'groupName' => 'หมวดหมู่ยาตาม Mims Class',
            'description' => 'หมวดหมู่ยาตาม Mims Class',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$LabSpecimenType',
            'groupName' => 'ชนิดสิ่งส่งตรวจ',
            'description' => 'ชนิดสิ่งส่งตรวจ',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$LabSpecimenContainer',
            'groupName' => 'ชนิดที่ใส่สิ่งส่งตรวจ',
            'description' => 'ชนิดที่ใส่สิ่งส่งตรวจ',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$EncounterType',
            'groupName' => 'ชนิดการเข้ารับบริการ',
            'description' => 'ชนิดการเข้ารับบริการ',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$DocumentCategory',
            'groupName' => 'หมวดหมู่เอกสาร',
            'description' => 'หมวดหมู่เอกสาร',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$DoctorType',
            'groupName' => 'ชนิดแพทย์',
            'description' => 'ชนิดแพทย์',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$DoctorSpecialty',
            'groupName' => 'สาขาความเชี่ยวชาญแพทย์',
            'description' => 'สาขาความเชี่ยวชาญแพทย์',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$AppointmentActivity',
            'groupName' => 'กิจกรรมการนัดหมาย',
            'description' => 'กิจกรรมการนัดหมาย',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$AppointmentType',
            'groupName' => 'ชนิดของการนัด',
            'description' => 'ชนิดของการนัด',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$PayerType',
            'groupName' => 'ชนิดผู้ชำระค่าใช้จ่าย',
            'description' => 'ชนิดผู้ชำระค่าใช้จ่าย',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$ICD10',
            'groupName' => 'ICD-10',
            'description' => 'ICD-10',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$ICD9CM',
            'groupName' => 'ICD-9-CM',
            'description' => 'ICD-9-CM',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$AllergyInformationSource',
            'groupName' => 'ข้อมูลการแพ้ แหล่งที่มาของข้อมูล',
            'description' => 'ข้อมูลการแพ้ แหล่งที่มาของข้อมูล',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$AllergyProbability',
            'groupName' => 'ข้อมูลการแพ้ ความน่าจะเป็น',
            'description' => 'ข้อมูลการแพ้ ความน่าจะเป็น ตาม Naranjo Scale',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$AllergySeverity',
            'groupName' => 'ข้อมูลการแพ้ ความรุนแรง',
            'description' => 'ข้อมูลการแพ้ ความรุนแรง',
            'propertiesTemplate' => null,
          ],
          [
            'groupKey' => '$AllergyType',
            'groupName' => 'ข้อมูลการแพ้ ชนิดการแพ้',
            'description' => 'ข้อมูลการแพ้ ชนิดการแพ้',
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
