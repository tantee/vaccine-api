<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //check if db is empty
        if (\App\Models\Master\MasterGroups::count()==0) return;
        
        //2021-06-05 Alter vaccine specification and add vaccine
        $master = \App\Models\Master\MasterGroups::find('covid19Vaccine');
        if ($master) {
            $master->propertiesTemplate = '<v-text-field label="Vaccine Total Dose" v-model="data.vaccine_total_dose" type="number"></v-text-field>
            <v-text-field label="Vaccine Interval" v-model="data.vaccine_interval" type="number"></v-text-field>
            <v-text-field label="MOPH Vaccine Code" v-model="data.moph_vaccine_code"></v-text-field>
            <v-text-field label="MOPH Vaccine Name" v-model="data.moph_vaccine_name"></v-text-field>
            <v-text-field label="MOPH Vaccine Manufacturer" v-model="data.moph_vaccine_manufacturer"></v-text-field>
            <v-text-field label="Appointment Activity Prefix" v-model="data.appointment_activity_prefix"></v-text-field>
            <v-text-field label="Color" v-model="data.color"></v-text-field>';
            $master->save();
        }

        $vaccine = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>"covid19Vaccine","itemCode"=>"00000000000000"],["itemValue"=>"Sinovac Covid19 Vaccine"]);
        $vaccineProps = $vaccine->properties;
        $vaccineProps["vaccine_interval"] = (isset($vaccineProps["vaccine_interval"])) ? $vaccineProps["vaccine_interval"] : 4;
        $vaccineProps["moph_vaccine_code"] = (isset($vaccineProps["moph_vaccine_code"])) ? $vaccineProps["moph_vaccine_code"] : "C19";
        $vaccineProps["moph_vaccine_name"] = (isset($vaccineProps["moph_vaccine_name"])) ? $vaccineProps["moph_vaccine_name"] : "Sinovac Covid-19 Vaccine";
        $vaccineProps["vaccine_total_dose"] = (isset($vaccineProps["vaccine_total_dose"])) ? $vaccineProps["vaccine_total_dose"] : "2";
        $vaccineProps["moph_vaccine_manufacturer"] = (isset($vaccineProps["moph_vaccine_manufacturer"])) ? $vaccineProps["moph_vaccine_manufacturer"] : "Sinovac Life Sciences";
        $vaccineProps["appointment_activity_prefix"] = (isset($vaccineProps["appointment_activity_prefix"])) ? $vaccineProps["appointment_activity_prefix"] : "1";
        $vaccineProps["color"] = (isset($vaccineProps["color"])) ? $vaccineProps["color"] : "orange darken-3";
        $vaccine->properties = $vaccineProps;
        $vaccine->save();

        $vaccine = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>"covid19Vaccine","itemCode"=>"08806507011325"],["itemValue"=>"AstraZeneca Covid19 Vaccine"]);
        $vaccineProps = $vaccine->properties;
        $vaccineProps["vaccine_interval"] = (isset($vaccineProps["vaccine_interval"])) ? $vaccineProps["vaccine_interval"] : 12;
        $vaccineProps["moph_vaccine_code"] = (isset($vaccineProps["moph_vaccine_code"])) ? $vaccineProps["moph_vaccine_code"] : "C19";
        $vaccineProps["moph_vaccine_name"] = (isset($vaccineProps["moph_vaccine_name"])) ? $vaccineProps["moph_vaccine_name"] : "AstraZeneca Covid-19 Vaccine";
        $vaccineProps["vaccine_total_dose"] = (isset($vaccineProps["vaccine_total_dose"])) ? $vaccineProps["vaccine_total_dose"] : "2";
        $vaccineProps["moph_vaccine_manufacturer"] = (isset($vaccineProps["moph_vaccine_manufacturer"])) ? $vaccineProps["moph_vaccine_manufacturer"] : "AstraZeneca";
        $vaccineProps["appointment_activity_prefix"] = (isset($vaccineProps["appointment_activity_prefix"])) ? $vaccineProps["appointment_activity_prefix"] : "2";
        $vaccineProps["color"] = (isset($vaccineProps["color"])) ? $vaccineProps["color"] : "purple darken-3";
        $vaccine->properties = $vaccineProps;
        $vaccine->save();

        $vaccine = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>"covid19Vaccine","itemCode"=>"05000456068253"],["itemValue"=>"AstraZeneca Covid19 Vaccine (SBS)"]);
        $vaccineProps = $vaccine->properties;
        $vaccineProps["vaccine_interval"] = (isset($vaccineProps["vaccine_interval"])) ? $vaccineProps["vaccine_interval"] : 12;
        $vaccineProps["moph_vaccine_code"] = (isset($vaccineProps["moph_vaccine_code"])) ? $vaccineProps["moph_vaccine_code"] : "C19";
        $vaccineProps["moph_vaccine_name"] = (isset($vaccineProps["moph_vaccine_name"])) ? $vaccineProps["moph_vaccine_name"] : "AstraZeneca Covid-19 Vaccine";
        $vaccineProps["vaccine_total_dose"] = (isset($vaccineProps["vaccine_total_dose"])) ? $vaccineProps["vaccine_total_dose"] : "2";
        $vaccineProps["moph_vaccine_manufacturer"] = (isset($vaccineProps["moph_vaccine_manufacturer"])) ? $vaccineProps["moph_vaccine_manufacturer"] : "AstraZeneca";
        $vaccineProps["appointment_activity_prefix"] = (isset($vaccineProps["appointment_activity_prefix"])) ? $vaccineProps["appointment_activity_prefix"] : "2";
        $vaccineProps["color"] = (isset($vaccineProps["color"])) ? $vaccineProps["color"] : "purple darken-3";
        $vaccine->properties = $vaccineProps;
        $vaccine->save();

        //2021-06-05 Add groupKey and items
        $master = \App\Models\Master\MasterGroups::firstOrCreate(["groupKey"=>'$DoctorSpecialty'],["groupName"=>"สาขาความเชี่ยวชาญแพทย์","description"=>"สาขาความเชี่ยวชาญแพทย์"]);
        $master = \App\Models\Master\MasterGroups::firstOrCreate(["groupKey"=>'$DoctorType'],["groupName"=>"ชนิดแพทย์","description"=>"ชนิดแพทย์"]);
        $master = \App\Models\Master\MasterGroups::firstOrCreate(["groupKey"=>'$AppointmentType'],["groupName"=>"ชนิดของการนัด","description"=>"ชนิดของการนัด"]);
        $master = \App\Models\Master\MasterGroups::firstOrCreate(["groupKey"=>'$AppointmentActivity'],["groupName"=>"กิจกรรมของการนัด","description"=>"กิจกรรมของการนัด"]);
        $master->groupName = "กิจกรรมของการนัด";
        $master->description = "กิจกรรมของการนัด";
        $master->propertiesTemplate = '<v-master-field v-model="data.productCode" groupKey="covid19Vaccine" label="ชนิดวัคซีน" clearable></v-master-field>';
        $master->save();

        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$DoctorSpecialty',"itemCode"=>"GEN"],["itemValue"=>"เวชปฏิบัติทั่วไป","itemValueEN"=>"General practitioner"]);
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$DoctorType',"itemCode"=>"FT"],["itemValue"=>"แพทย์ประจำ","itemValueEN"=>"Full-time doctors"]);
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"101"],["itemValue"=>"Sinovac - เข็มที่ 1","itemValueEN"=>"Sinovac - First Dose"]);
        $master->itemValue = "Sinovac - เข็มที่ 1";
        $master->itemValueEN = "Sinovac - First Dose";
        $master->properties = ["productCode"=>"00000000000000"];
        $master->save();
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"102"],["itemValue"=>"Sinovac - เข็มที่ 2","itemValueEN"=>"Sinovac - Second Dose"]);
        $master->itemValue = "Sinovac - เข็มที่ 2";
        $master->itemValueEN = "Sinovac - Second Dose";
        $master->properties = ["productCode"=>"00000000000000"];
        $master->save();
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"201"],["itemValue"=>"AstraZeneca - เข็มที่ 1","itemValueEN"=>"AstraZeneca - First Dose"]);
        $master->properties = ["productCode"=>"05000456068253"];
        $master->save();
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"202"],["itemValue"=>"AstraZeneca - เข็มที่ 2","itemValueEN"=>"AstraZeneca - Second Dose"]);
        $master->properties = ["productCode"=>"05000456068253"];
        $master->save();
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"901"],["itemValue"=>"เข็มที่ 1","itemValueEN"=>"First Dose"]);
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"902"],["itemValue"=>"เข็มที่ 2","itemValueEN"=>"Second Dose"]);
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentType',"itemCode"=>"VACCINE"],["itemValue"=>"นัดรับวัคซีน COVID19","itemValueEN"=>"COVID19 Vaccine"]);

        //2021-06-05 Add clinic, location and doctors
        $clinic = \App\Models\Master\Clinics::firstOrCreate(["clinicCode"=>"VACCINE"],["clinicName"=>"คลินิกวัคซีน COVID19","clinicNameEN"=>"COVID19 Vaccine Clinic","encounterType"=>"AMB","locationCode"=>"LOC01"]);
        $doctor = \App\Models\Master\Doctors::firstOrCreate(["doctorCode"=>"CN01"],["doctorType"=>"FT","nameTH"=>"แพทย์กองกลางคลินิก","nameEN"=>"Doctor","specialty"=>"GEN","licenseNo"=>"-","personId"=>"-","telephone"=>"-"]);
        $location = \App\Models\Master\Locations::firstOrCreate(["locationCode"=>"LOC01"],["locationType"=>"OPD","locationName"=>"สถานที่ฉีดวัคซีน","locationTelephone"=>"-"]);

        //2021-06-05 change old appointment detail
        $appointments = \App\Models\Appointment\Appointments::where('clinicCode','BKK001')->get();
        foreach($appointments as $appointment) {
            $appointment->clinicCode = "VACCINE";
            $appointment->doctorCode = 'CN01';
            $appointment->appointmentType = 'VACCINE';
            $appointment->appointmentActivity = '102';
            $appointment->save();
        }

        //2021-06-08 Add master group covid19VaccineSuggestion
        $master = \App\Models\Master\MasterGroups::firstOrCreate(
                        ["groupKey"=>'covid19VaccineSuggestion'],
                        [
                            "groupName"=>"กำหนดชนิดวัคชีนตามกลุ่มผู้รับบริการ",
                            "description"=>"กำหนดชนิดวัคชีนตามกลุ่มผู้รับบริการ",
                            "propertiesTemplate"=>'<v-master-field v-model="data.productCode" groupKey="covid19Vaccine" label="ชนิดวัคซีน" clearable></v-master-field>'
                        ]
                    );
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'covid19VaccineSuggestion',"itemCode"=>"MOPH"],["itemValue"=>"นัดผ่านหมอพร้อมหรือไทยร่วมใจ","properties"=>["productCode"=>"05000456068253"]]);
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'covid19VaccineSuggestion',"itemCode"=>"Default"],["itemValue"=>"ค่าเริ่มต้น (ไม่ระบุกลุ่ม)","properties"=>["productCode"=>"05000456068253"]]);
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'covid19VaccineSuggestion',"itemCode"=>"Sinovac"],["itemValue"=>"ระบุ Sinovac","properties"=>["productCode"=>"00000000000000"]]);
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'covid19VaccineSuggestion',"itemCode"=>"Astrazeneca"],["itemValue"=>"ระบุ Astrazenaca","properties"=>["productCode"=>"05000456068253"]]);

        //2021-06-08 Fix administration view template
        $template = \App\Models\Document\DocumentsTemplates::where('templateCode','cv19-vaccine-administration')->first();
        if ($template) {
            $template->viewTemplate = '<v-container>
            <v-row align="center">
              <v-col cols="4" md="2">
                <span class="text-h6">Vital signs</span>
              </v-col>
              <v-col cols="4" md="2">
                <v-text-field v-model="data.temperature" :rules="[rules.require(),rules.numeric(),rules.range(30,45)]" label="Temperature" append="°C"></v-text-field>
              </v-col>
              <v-col cols="4" md="2">
                <v-text-field v-model="data.bloodPressureSystolic" :rules="[rules.require(),rules.numeric()]" label="BP Systolic" append="mmHg"></v-text-field>
              </v-col>
              <v-col cols="4" md="2">
                <v-text-field v-model="data.bloodPressureDiastolic" :rules="[rules.require(),rules.numeric()]" label="BP Diastolic" append="mmHg"></v-text-field>
              </v-col>
              <v-col cols="4" md="2">
                <v-text-field v-model="data.height" :rules="[rules.numeric()]" label="Height" append="cm"></v-text-field>
              </v-col>
              <v-col cols="4" md="2">
                <v-text-field v-model="data.weight" :rules="[rules.numeric()]" label="Weight" append="Kg"></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col>
                <v-text-barcode v-model="data.gs1code" label="GS1 Barcode"></v-text-barcode>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12" md="8">
                <v-master-field v-model="data.productCode" groupKey="covid19Vaccine" label="ชนิดวัคซีน" :rules="[rules.require()]"></v-master-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-date-time-field v-model="data.adminDateTime" label="วันที่ฉีด" :rules="[rules.require()]"></v-date-time-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="data.lotNo" label="Lot No." :rules="[rules.require()]"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="data.serialNo" label="Serial No." :rules="[rules.require()]"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="data.expDate" label="Expiry date" :rules="[rules.require()]"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-master-field v-model="data.adminRoute" groupKey="covid19VaccineAdminRoute" label="Route" :rules="[rules.require()]"></v-master-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-master-field v-model="data.adminSite" groupKey="covid19VaccineAdminSite" label="ตำแหน่งที่ฉีดยา"></v-master-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="data.adminSiteOther" label="ตำแหน่งอื่นๆ"></v-text-field>
              </v-col>
            </v-row>';
            $template->save();
        }

        //2021-06-30 Add Sinopharm Vaccine
        $vaccine = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>"covid19Vaccine","itemCode"=>"55555555555555"],["itemValue"=>"Sinopharm Covid19 Vaccine"]);
        $vaccineProps = $vaccine->properties;
        $vaccineProps["vaccine_interval"] = (isset($vaccineProps["vaccine_interval"])) ? $vaccineProps["vaccine_interval"] : 4;
        $vaccineProps["moph_vaccine_code"] = (isset($vaccineProps["moph_vaccine_code"])) ? $vaccineProps["moph_vaccine_code"] : "C19";
        $vaccineProps["moph_vaccine_name"] = (isset($vaccineProps["moph_vaccine_name"])) ? $vaccineProps["moph_vaccine_name"] : "Sinopharm Covid-19 Vaccine";
        $vaccineProps["vaccine_total_dose"] = (isset($vaccineProps["vaccine_total_dose"])) ? $vaccineProps["vaccine_total_dose"] : "2";
        $vaccineProps["moph_vaccine_manufacturer"] = (isset($vaccineProps["moph_vaccine_manufacturer"])) ? $vaccineProps["moph_vaccine_manufacturer"] : "Sinopharm";
        $vaccineProps["appointment_activity_prefix"] = (isset($vaccineProps["appointment_activity_prefix"])) ? $vaccineProps["appointment_activity_prefix"] : "3";
        $vaccineProps["color"] = (isset($vaccineProps["color"])) ? $vaccineProps["color"] : "teal darken-2";
        $vaccine->properties = $vaccineProps;
        $vaccine->save();

        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"301"],["itemValue"=>"Sinopharm - เข็มที่ 1","itemValueEN"=>"Sinopharm - First Dose"]);
        $master->properties = ["productCode"=>"55555555555555"];
        $master->save();
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"302"],["itemValue"=>"Sinopharm - เข็มที่ 2","itemValueEN"=>"Sinopharm - Second Dose"]);
        $master->properties = ["productCode"=>"55555555555555"];
        $master->save();

        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'covid19VaccineSuggestion',"itemCode"=>"Sinopharm"],["itemValue"=>"ระบุ Sinopharm","properties"=>["productCode"=>"55555555555555"]]);

        //2021-07-15 Add Astrazeneca(JPN)
        $vaccine = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>"covid19Vaccine","itemCode"=>"14987222001912"],["itemValue"=>"AstraZeneca Covid19 Vaccine (JPN)"]);
        $vaccineProps = $vaccine->properties;
        $vaccineProps["vaccine_interval"] = (isset($vaccineProps["vaccine_interval"])) ? $vaccineProps["vaccine_interval"] : 12;
        $vaccineProps["moph_vaccine_code"] = (isset($vaccineProps["moph_vaccine_code"])) ? $vaccineProps["moph_vaccine_code"] : "C19";
        $vaccineProps["moph_vaccine_name"] = (isset($vaccineProps["moph_vaccine_name"])) ? $vaccineProps["moph_vaccine_name"] : "AstraZeneca Covid-19 Vaccine";
        $vaccineProps["vaccine_total_dose"] = (isset($vaccineProps["vaccine_total_dose"])) ? $vaccineProps["vaccine_total_dose"] : "2";
        $vaccineProps["moph_vaccine_manufacturer"] = (isset($vaccineProps["moph_vaccine_manufacturer"])) ? $vaccineProps["moph_vaccine_manufacturer"] : "AstraZeneca";
        $vaccineProps["appointment_activity_prefix"] = (isset($vaccineProps["appointment_activity_prefix"])) ? $vaccineProps["appointment_activity_prefix"] : "2";
        $vaccineProps["color"] = (isset($vaccineProps["color"])) ? $vaccineProps["color"] : "purple darken-3";
        $vaccine->properties = $vaccineProps;
        $vaccine->save();

        //2021-07-21 Make vital sign optional
        $template = \App\Models\Document\DocumentsTemplates::where('templateCode','cv19-vaccine-administration')->first();
        $templateEdit = $template->editTemplate;
        $templateEdit = mb_ereg_replace('v-model=\"data\.bloodPressureSystolic\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.bloodPressureSystolic" :rules="[rules.numeric()]"',$templateEdit);
        $templateEdit = mb_ereg_replace('v-model=\"data\.bloodPressureDiastolic\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.bloodPressureDiastolic" :rules="[rules.numeric()]"',$templateEdit);
        $templateEdit = mb_ereg_replace('v-model=\"data\.height\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.height" :rules="[rules.numeric()]"',$templateEdit);
        $templateEdit = mb_ereg_replace('v-model=\"data\.weight\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.weight" :rules="[rules.numeric()]"',$templateEdit);
        $template->editTemplate = $templateEdit;
        $templateView = $template->viewTemplate;
        $templateView = mb_ereg_replace('v-model=\"data\.bloodPressureSystolic\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.bloodPressureSystolic" :rules="[rules.numeric()]"',$templateView);
        $templateView = mb_ereg_replace('v-model=\"data\.bloodPressureDiastolic\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.bloodPressureDiastolic" :rules="[rules.numeric()]"',$templateView);
        $templateView = mb_ereg_replace('v-model=\"data\.height\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.height" :rules="[rules.numeric()]"',$templateView);
        $templateView = mb_ereg_replace('v-model=\"data\.weight\" :rules=\"\[rules\.require\(\),rules\.numeric\(\)\]\"','v-model="data.weight" :rules="[rules.numeric()]"',$templateView);
        $template->viewTemplate = $templateView;
        $template->save();


        //2021-08-05 Add Pfizer
        $vaccine = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>"covid19Vaccine","itemCode"=>"00359267100023"],["itemValue"=>"Pfizer Covid19 Vaccine"]);
        $vaccineProps = $vaccine->properties;
        $vaccineProps["vaccine_interval"] = (isset($vaccineProps["vaccine_interval"])) ? $vaccineProps["vaccine_interval"] : 3;
        $vaccineProps["moph_vaccine_code"] = (isset($vaccineProps["moph_vaccine_code"])) ? $vaccineProps["moph_vaccine_code"] : "C19";
        $vaccineProps["moph_vaccine_name"] = (isset($vaccineProps["moph_vaccine_name"])) ? $vaccineProps["moph_vaccine_name"] : "Pfizer Covid-19 Vaccine";
        $vaccineProps["vaccine_total_dose"] = (isset($vaccineProps["vaccine_total_dose"])) ? $vaccineProps["vaccine_total_dose"] : "2";
        $vaccineProps["moph_vaccine_manufacturer"] = (isset($vaccineProps["moph_vaccine_manufacturer"])) ? $vaccineProps["moph_vaccine_manufacturer"] : "Pfizer, BioNTech";
        $vaccineProps["appointment_activity_prefix"] = (isset($vaccineProps["appointment_activity_prefix"])) ? $vaccineProps["appointment_activity_prefix"] : "4";
        $vaccineProps["color"] = (isset($vaccineProps["color"])) ? $vaccineProps["color"] : "blue darken-1";
        $vaccine->properties = $vaccineProps;
        $vaccine->save();

        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"401"],["itemValue"=>"Pfizer - เข็มที่ 1","itemValueEN"=>"Pfizer - First Dose"]);
        $master->properties = ["productCode"=>"00359267100023"];
        $master->save();
        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'$AppointmentActivity',"itemCode"=>"402"],["itemValue"=>"Pfizer - เข็มที่ 2","itemValueEN"=>"Pfizer - Second Dose"]);
        $master->properties = ["productCode"=>"00359267100023"];
        $master->save();

        $master = \App\Models\Master\MasterItems::firstOrCreate(["groupKey"=>'covid19VaccineSuggestion',"itemCode"=>"Pfizer"],["itemValue"=>"ระบุ Pfizer","properties"=>["productCode"=>"00359267100023"]]);

    }
}
