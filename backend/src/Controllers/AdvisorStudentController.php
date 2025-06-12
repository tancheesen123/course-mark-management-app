<?php

namespace App\Controllers;

use mysqli;

class AdvisorStudentController
{
    // 获取学生与顾问的关系
    public function getAdvisees($advisor_id)
    {
        // 数据库连接设置
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "course_mark_management";

        // 创建连接
        $conn = new mysqli($servername, $username, $password, $dbname);

        // 检查连接是否成功
        if ($conn->connect_error) {
            die("连接失败: " . $conn->connect_error);
        }

        // 查询学生与顾问的关系
        $sql = "
          SELECT u.username AS student_name, as_rel.matric_number, as_rel.gpa, as_rel.semester, as_rel.year, as_rel.risk
          FROM advisor_student AS as_rel
          JOIN user u ON as_rel.student_id = u.user_id
          WHERE as_rel.advisor_id = $advisor_id;
        ";

        $result = $conn->query($sql);

        // 将数据存入数组
        $students = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
        }

        $conn->close(); // 关闭连接

        // 返回 JSON 格式数据
        echo json_encode($students);
    }
}
