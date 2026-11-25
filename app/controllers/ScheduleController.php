<?php
declare(strict_types=1);

final class ScheduleController {
    private const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    private const WEEK_DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    private const FIELDS = 'id,bus_id,day_of_week,route_name,origin_location,parking_location,destination_location,route_description,departure_time,arrival_time,evening_departure_time,evening_arrival_time';

    public function index(PDO $pdo): void {
        require_login();
        $todayName=date('l'); $role=$_SESSION['user']['role'];
        $showFullWeek=$role==='admin' && (($_GET['view']??'')==='week');
        $sql='SELECT '.self::FIELDS.' FROM schedule_entries';$params=[];$hasWhere=false;
        if($role==='parent'){$sql.=' WHERE bus_id IN (SELECT DISTINCT bus_id FROM students WHERE parent_id=?)';$params[]=(int)$_SESSION['user']['id'];$hasWhere=true;}
        elseif($role==='driver'){$sql.=' WHERE bus_id IN (SELECT id FROM buses WHERE driver_id=?)';$params[]=(int)$_SESSION['user']['id'];$hasWhere=true;}
        if(!$showFullWeek){$sql.=$hasWhere?' AND day_of_week=?':' WHERE day_of_week=?';$params[]=$todayName;}
        $sql.=' ORDER BY FIELD(day_of_week,"Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"),departure_time';
        $q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();$isClosedDay=in_array($todayName,['Saturday','Sunday'],true);if($isClosedDay && !$showFullWeek)$rows=[];
        $days=$showFullWeek?self::WEEK_DAYS:[$todayName];$rowsByDay=array_fill_keys(self::WEEK_DAYS,[]);
        foreach($rows as $row)$rowsByDay[$row['day_of_week']][]=$row;
        $isClosedDay=in_array($todayName,['Saturday','Sunday'],true);
        require __DIR__ . '/../views/schedules/index.html';
    }

    public function form(PDO $pdo): void {
        require_role(['admin']);
        $id=(int)($_GET['id']??0);
        $entry=['id'=>0,'bus_id'=>0,'day_of_week'=>'Monday','route_name'=>'','origin_location'=>'','parking_location'=>'','destination_location'=>'','route_description'=>'','departure_time'=>'07:00','arrival_time'=>'08:00','evening_departure_time'=>null,'evening_arrival_time'=>null];
        if($id){$q=$pdo->prepare('SELECT '.self::FIELDS.' FROM schedule_entries WHERE id=?');$q->execute([$id]);$entry=$q->fetch();if(!$entry)throw new InvalidArgumentException('Timetable entry not found.');}
        $days=self::DAYS;$buses=$pdo->query('SELECT id,bus_number FROM buses ORDER BY bus_number')->fetchAll();
        require __DIR__ . '/../views/schedules/form.html';
    }

    public function save(PDO $pdo): void {
        require_role(['admin']);verify_csrf();
        $id=(int)($_POST['id']??0);$busId=(int)($_POST['bus_id']??0);$day=post_string('day_of_week',12);$name=post_string('route_name',100);
        $origin=post_string('origin_location',180);$parking=post_string('parking_location',180);$destination=post_string('destination_location',180);$description=post_string('route_description',500);
        $departure=(string)($_POST['departure_time']??'');$arrival=(string)($_POST['arrival_time']??'');
        $eveningDeparture=trim((string)($_POST['evening_departure_time']??''));$eveningArrival=trim((string)($_POST['evening_arrival_time']??''));
        if(!in_array($day,self::DAYS,true)||$name===''||$origin===''||$parking===''||$destination===''||$description==='')throw new InvalidArgumentException('Set a weekday, bus, route, locations and road notes. Travel operates Monday to Friday.');
        $check=$pdo->prepare('SELECT id FROM buses WHERE id=?');$check->execute([$busId]);if(!$check->fetch())throw new InvalidArgumentException('Assign this route to a valid bus.');
        foreach([$departure,$arrival] as $time)if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$time))throw new InvalidArgumentException('Enter valid morning departure and arrival times.');
        if(($eveningDeparture==='')!==($eveningArrival===''))throw new InvalidArgumentException('Enter both evening times or leave both blank.');
        if($eveningDeparture!==''&&(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$eveningDeparture)||!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$eveningArrival)||$eveningDeparture>=$eveningArrival))throw new InvalidArgumentException('Enter a valid evening departure and later arrival time.');
        if($id){$q=$pdo->prepare('UPDATE schedule_entries SET bus_id=?,day_of_week=?,route_name=?,origin_location=?,parking_location=?,destination_location=?,route_description=?,departure_time=?,arrival_time=?,evening_departure_time=?,evening_arrival_time=? WHERE id=?');$q->execute([$busId,$day,$name,$origin,$parking,$destination,$description,$departure,$arrival,$eveningDeparture?:null,$eveningArrival?:null,$id]);}
        else{$q=$pdo->prepare('INSERT INTO schedule_entries(bus_id,day_of_week,route_name,origin_location,parking_location,destination_location,route_description,departure_time,arrival_time,evening_departure_time,evening_arrival_time,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');$q->execute([$busId,$day,$name,$origin,$parking,$destination,$description,$departure,$arrival,$eveningDeparture?:null,$eveningArrival?:null,(int)$_SESSION['user']['id']]);}
        flash('Travel timetable and route saved.');redirect('index.php?page=schedules');
    }

    public function delete(PDO $pdo): void {
        require_role(['admin']);verify_csrf();$id=(int)($_POST['id']??0);if($id<1)throw new InvalidArgumentException('Select a valid timetable entry.');
        $q=$pdo->prepare('DELETE FROM schedule_entries WHERE id=?');$q->execute([$id]);flash('Timetable entry deleted.');redirect('index.php?page=schedules');
    }
}