<b>สรุปยอดผู้รับวัคซีน วันที่ {{ $beginDate }} @if ($beginDate!=$endDate) ถึงวันที่ {{ $endDate }} @endif</b><br/>
{{ $hospital_name }} ({{ $hospital_code }})<br/>
<br />
ยอดผู้รับวัคซีนทั้งหมด {{ $total }} ราย<br/>
<br/>
ยอดผู้รับวัคซีน เข็มที่ 1 - {{ $total_dose_1 }} ราย<br/>
ยอดผู้รับวัคซีน เข็มที่ 2 - {{ $total_dose_2 }} ราย<br/>
<br/>
ยอดผู้รับวัคซีนแยกตามกลุ่มนัดหมาย<br/>
<table>
  <tr>
    <th>กลุ่ม</th>
    <th>จำนวนนัด</th>
    <th>จำนวนมาฉีด</th>
    <th>เข็ม 1</th>
    <th>เข็ม 2</th>
  </tr>
  @foreach ($group as $key=>$item)
  <tr>
    <td>{{ $key }}</td>
    <td>{{ $item["appointed"] }}</td>
    <td>{{ $item["visited"] }}</td>
    <td>{{ $item["dose_1"] }}</td>
    <td>{{ $item["dose_2"] }}</td>
  </tr>
  @endforeach
</table>
*MOPH - นัดหมายผ่านระบบ MOPH IC รวมถึงนัดหมายจากไทยร่วมใจที่ส่งข้อมูลผ่าน MOPH IC
<br/>
<br/>
ยอดผู้รับวัคซีนแยกตามชนิดวัคซีน<br/>
<table border="1">
  <tr>
    <th>ชนิดวัคซีน</th>
    <th>จำนวนมาฉีด</th>
  </tr>
  @foreach ($vaccine as $key=>$value)
  <tr>
    <td>{{ $key }}</td>
    <td>{{ $value }}</td>
  </tr>
  @endforeach
</table>

<style>
table, th, td {
  border: 1px solid black;
}

table {
  border-collapse: collapse;
}

th, td {
  padding: 5px;
}
</style>