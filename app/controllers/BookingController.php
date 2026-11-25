<?php
declare(strict_types=1);

final class BookingController {
    private function ownChild(PDO $pdo, int $studentId): array {
        $q=$pdo->prepare('SELECT s.id,s.full_name,s.student_code,s.grade,s.bus_id,b.bus_number,b.plate_number,b.capacity,b.driver_id,d.full_name AS driver_name,d.phone_number AS driver_phone,d.avatar_filename AS driver_avatar FROM students s JOIN buses b ON b.id=s.bus_id LEFT JOIN users d ON d.id=b.driver_id WHERE s.id=? AND s.parent_id=? LIMIT 1');
        $q->execute([$studentId,(int)$_SESSION['user']['id']]); $student=$q->fetch();
        if(!$student) throw new InvalidArgumentException('That child is not registered to your parent account.');
        return $student;
    }
    private function validDate(string $value): ?DateTimeImmutable {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        return $date && $date->format('Y-m-d')===$value ? $date : null;
    }
    private function tripTimes(array $entry,string $period): array {
        if($period==='morning') return [$entry['departure_time'],$entry['arrival_time']];
        if($period==='evening' && !empty($entry['evening_departure_time']) && !empty($entry['evening_arrival_time'])) return [$entry['evening_departure_time'],$entry['evening_arrival_time']];
        return [null,null];
    }

    public function index(PDO $pdo): void {
        require_role(['parent']);
        $q=$pdo->prepare('SELECT r.id,r.travel_date,r.trip_period,r.seat_number,r.created_at,s.full_name AS student_name,b.bus_number,b.plate_number,e.day_of_week,e.route_name,CASE WHEN r.trip_period="evening" THEN e.evening_departure_time ELSE e.departure_time END AS departure_time,CASE WHEN r.trip_period="evening" THEN e.evening_arrival_time ELSE e.arrival_time END AS arrival_time,e.parking_location,d.full_name AS driver_name,d.phone_number AS driver_phone,d.avatar_filename AS driver_avatar FROM ride_bookings r JOIN students s ON s.id=r.student_id AND s.parent_id=r.parent_id JOIN buses b ON b.id=r.bus_id JOIN schedule_entries e ON e.id=r.schedule_entry_id LEFT JOIN users d ON d.id=b.driver_id WHERE r.parent_id=? ORDER BY r.travel_date,e.departure_time,r.id DESC');
        $q->execute([(int)$_SESSION['user']['id']]); $rows=$q->fetchAll(); require __DIR__ . '/../views/bookings/index.html';
    }

    public function create(PDO $pdo): void {
        require_role(['parent']); $student=$this->ownChild($pdo,(int)($_GET['student_id']??0));
        $q=$pdo->prepare('SELECT id,day_of_week,route_name,parking_location,departure_time,arrival_time,evening_departure_time,evening_arrival_time FROM schedule_entries WHERE bus_id=? AND origin_location<>"" AND parking_location<>"" AND destination_location<>"" ORDER BY FIELD(day_of_week,"Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"),departure_time');
        $q->execute([(int)$student['bus_id']]); $scheduleOptions=$q->fetchAll();
        $travelDate=(string)($_GET['travel_date']??''); $scheduleId=(int)($_GET['schedule_id']??0); $tripPeriod=(string)($_GET['trip_period']??'morning');
        $selectedEntry=null; $bookedSeats=[]; $formError=''; $selectedDeparture=''; $selectedArrival='';
        if($travelDate!=='' && $scheduleId>0){
            $date=$this->validDate($travelDate);
            foreach($scheduleOptions as $option) if((int)$option['id']===$scheduleId){$selectedEntry=$option;break;}
            if(!$date || $date->format('Y-m-d')<date('Y-m-d')) $formError='Choose today or a future travel date.';
            elseif(!$selectedEntry) $formError='Choose a scheduled trip for this child’s assigned bus.';
            elseif(!in_array($tripPeriod,['morning','evening'],true)) $formError='Choose a valid trip period.';
            else {[$selectedDeparture,$selectedArrival]=$this->tripTimes($selectedEntry,$tripPeriod);
                if(!$selectedDeparture || !$selectedArrival) $formError='An evening return time has not been added for this route.';
                elseif($date->format('l')!==$selectedEntry['day_of_week']) $formError='The selected date does not match this trip’s weekday.';
                elseif($date->format('Y-m-d')===date('Y-m-d') && date('H:i:s')>=$selectedDeparture) $formError='This trip has already departed. Choose a future date.';
                else{$q=$pdo->prepare('SELECT seat_number FROM ride_bookings WHERE bus_id=? AND schedule_entry_id=? AND travel_date=? AND trip_period=? ORDER BY seat_number');$q->execute([(int)$student['bus_id'],$scheduleId,$travelDate,$tripPeriod]);$bookedSeats=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));}
            }
        }
        require __DIR__ . '/../views/bookings/form.html';
    }

    public function save(PDO $pdo): void {
        require_role(['parent']);verify_csrf();$student=$this->ownChild($pdo,(int)($_POST['student_id']??0));
        $scheduleId=(int)($_POST['schedule_id']??0);$seat=(int)($_POST['seat_number']??0);$travelDate=(string)($_POST['travel_date']??'');$tripPeriod=(string)($_POST['trip_period']??'');$date=$this->validDate($travelDate);
        if(!$date || $date->format('Y-m-d')<date('Y-m-d'))throw new InvalidArgumentException('Choose today or a future travel date.');
        if(!in_array($tripPeriod,['morning','evening'],true))throw new InvalidArgumentException('Choose a valid trip period.');
        $q=$pdo->prepare('SELECT id,day_of_week,departure_time,arrival_time,evening_departure_time,evening_arrival_time FROM schedule_entries WHERE id=? AND bus_id=? AND origin_location<>"" AND parking_location<>"" AND destination_location<>""');
        $q->execute([$scheduleId,(int)$student['bus_id']]);$entry=$q->fetch();if(!$entry)throw new InvalidArgumentException('Choose a scheduled trip for this child’s assigned bus.');
        [$departure,$arrival]=$this->tripTimes($entry,$tripPeriod);if(!$departure || !$arrival)throw new InvalidArgumentException('An evening return time has not been added for this route.');
        if($date->format('l')!==$entry['day_of_week'])throw new InvalidArgumentException('The selected date does not match this trip’s weekday.');
        if($date->format('Y-m-d')===date('Y-m-d') && date('H:i:s')>=$departure)throw new InvalidArgumentException('This trip has already departed. Choose a future date.');
        if($seat<1 || $seat>(int)$student['capacity'])throw new InvalidArgumentException('Choose a valid seat number.');
        try{$q=$pdo->prepare('INSERT INTO ride_bookings(parent_id,student_id,bus_id,schedule_entry_id,travel_date,trip_period,seat_number) VALUES(?,?,?,?,?,?,?)');$q->execute([(int)$_SESSION['user']['id'],(int)$student['id'],(int)$student['bus_id'],$scheduleId,$travelDate,$tripPeriod,$seat]);}
        catch(PDOException $ex){if((string)$ex->getCode()==='23000')throw new InvalidArgumentException('That seat or a booking for this child on this trip was just taken. Check availability and choose again.');throw $ex;}
        flash(ucfirst($tripPeriod).' seat '.$seat.' booked for '.$student['full_name'].'.');redirect('index.php?page=bookings');
    }

    public function cancel(PDO $pdo): void {
        require_role(['parent']);verify_csrf();$today=date('Y-m-d');$now=date('H:i:s');
        $q=$pdo->prepare('DELETE r FROM ride_bookings r JOIN schedule_entries e ON e.id=r.schedule_entry_id WHERE r.id=? AND r.parent_id=? AND (r.travel_date>? OR (r.travel_date=? AND (CASE WHEN r.trip_period="evening" THEN e.evening_departure_time ELSE e.departure_time END)>?))');
        $q->execute([(int)($_POST['id']??0),(int)$_SESSION['user']['id'],$today,$today,$now]);
        if($q->rowCount()!==1)throw new InvalidArgumentException('This booking could not be cancelled. The trip may have departed or it may not belong to your account.');
        flash('Booking cancelled; the seat is available again.');redirect('index.php?page=bookings');
    }
}