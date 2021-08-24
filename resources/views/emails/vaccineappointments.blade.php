รายการสรุปยอดนัดหมายล่วงหน้า ระหว่างวันที่ {{ $beginDate }} ถึงวันที่ {{ $endDate }}<br/>
ข้อมูล ณ วันที่ {{ $reportDate }}<br/>
{{ $hospital_name }} ({{ $hospital_code }})<br/>
<br/>
<table>
  <tr>
    <th>วันที่นัด</th>
    <th>จำนวนนัดทั้งหมด</th>
    <th>นัดแยกตามกลุ่ม</th>
    <th>นัดแยกตามชนิดวัคซีน</th>
  </tr>
  @foreach ($appointments as $key=>$item)
  <tr>
    <td>{{ $item["date"] }}</td>
    <td>{{ $item["total_appointed"] }}</td>
    <td>
      @foreach ($item["group"] as $keyA=>$itemA)
      {{ $keyA }} - {{ $itemA }}<br/>
      @endforeach
    </td>
    <td>
      @foreach ($item["vaccine"] as $keyB=>$itemB)
      {{ $keyB }} - {{ $itemB }}<br/>
      @endforeach
    </td>
  </tr>
  @endforeach
</table>
*MOPH - นัดหมายผ่านระบบ MOPH IC รวมถึงนัดหมายจากไทยร่วมใจที่ส่งข้อมูลผ่าน MOPH IC

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