/* class ticket.php */

<?php
class Ticket {	
	private $ticketsTable = 'tickets';	
	private $ticketReplyTable = 'ticket_replies';
	private $issueTable = 'issue';
	private $userTable = 'ticket_user';
	private $conn;
	public function __construct($db){
        $this->conn = $db;
    }
	public function insert(){
		if($this->subject && $this->message) {
			$stmt = $this->conn->prepare("
			INSERT INTO ".$this->ticketsTable."(`title`, `message`, `userid`, `issue_id`)
			VALUES(?,?,?,?)");
			$this->subject = htmlspecialchars(strip_tags($this->subject));
			$this->message = htmlspecialchars(strip_tags($this->message));
			$this->issue = htmlspecialchars(strip_tags($this->issue));			
			$stmt->bind_param("ssii", $this->subject, $this->message, $_SESSION["userid"], $this->issue);
			if($stmt->execute()){
				return true;
			}		
		}
	}

	public function getTicket(){		
		$sqlWhere = '';		
		$status = 'open';
		$order = ' ORDER BY id DESC';
		if(!empty($this->status) && $this->status == 'closed') {
			$status = 'closed';
		} elseif(!empty($this->order) && $this->order == 'oldest') {
			$order = ' ORDER BY id ASC';
		} 
		if(!empty($this->mentioned) && $this->mentioned) {
			$sqlWhere .= " AND ticket.mentioned like '%".$this->mentioned."%'";
		} else if(!empty($this->userId)) {
			$sqlWhere = " AND ticket.userid = '".$this->userId."'";
		}		
		$sqlQuery = "
			SELECT ticket.id, ticket.title, ticket.message, ticket.userid, ticket.mentioned, ticket.created, ticket.status, user.name
			FROM ".$this->ticketsTable." ticket
			LEFT JOIN ".$this->userTable." user ON user.userid = ticket.userid
			LEFT JOIN ".$this->issueTable." issue ON issue.id = ticket.issue_id
			WHERE ticket.status = '".$status."' $sqlWhere $order";	
		$stmt = $this->conn->prepare($sqlQuery);			
		$stmt->execute();
		$result = $stmt->get_result();
		return $result;
	}	
	
	public function getTicketDetail(){			
		if($_SESSION["userid"] && $this->ticket_id) {				
			$sqlQuery = "
				SELECT ticket.id, ticket.title, ticket.message, ticket.userid, ticket.mentioned, ticket.created, ticket.status, user.name, reply.comments, reply.created AS reply_date
				FROM ".$this->ticketsTable." ticket
				LEFT JOIN ".$this->ticketReplyTable." reply ON ticket.id = reply.ticket_id
				LEFT JOIN ".$this->userTable." user ON user.userid = ticket.userid				
				WHERE ticket.id = '".$this->ticket_id."'";	
			$stmt = $this->conn->prepare($sqlQuery);			
			$stmt->execute();
			$result = $stmt->get_result();
			return $result;
		}
	}
	
	function getTicketCountWithStatus ($status) {		
		$sqlWhere = '';
		$stmt = $this->conn->prepare("SELECT count(*) AS total
		FROM ".$this->ticketsTable." 
		WHERE status = ? $sqlWhere");		
		$stmt->bind_param("s", $status);
		$stmt->execute();			
		$result = $stmt->get_result();	
		$reply = $result->fetch_assoc();
		return $reply['total'];
	}
	
	public function getReplyCount() {
		if($this->id) {
			$stmt = $this->conn->prepare("SELECT count(*) AS total
			FROM ".$this->ticketReplyTable." 
			WHERE id = ?");		
			$stmt->bind_param("i", $this->id);
			$stmt->execute();			
			$result = $stmt->get_result();	
			$reply = $result->fetch_assoc();
			return $reply['total'];
		}
	}
	
	function saveTicketReply() {
		if($_SESSION["userid"] && $this->ticketId && $this->replyMessage) {
			$stmt = $this->conn->prepare("
			INSERT INTO ".$this->ticketReplyTable."(`ticket_id`, `comments`, `created_by`)
			VALUES(?,?,?)");
			$this->replyMessage = htmlspecialchars(strip_tags($this->replyMessage));
			$this->ticketId = htmlspecialchars(strip_tags($this->ticketId));
			$stmt->bind_param("iss", $this->ticketId, $this->replyMessage, $_SESSION["userid"]);
			if($stmt->execute()){
				return true;
			}		
		}	
	}	
	
	function openTicket() {		
		if($_SESSION["userid"] && $this->ticketId) {
			$stmt = $this->conn->prepare("
			UPDATE ".$this->ticketsTable." 
			SET status = 'open' 
			WHERE id = ?");	
			$this->ticketId = htmlspecialchars(strip_tags($this->ticketId));		
			$stmt->bind_param("i", $this->ticketId);
			if($stmt->execute()){
				return true;
			}		
		}		
	}
	
	function closeTicket() {		
		if($_SESSION["userid"] && $this->ticketId) {
			$stmt = $this->conn->prepare("
			UPDATE ".$this->ticketsTable." 
			SET status = 'closed' 
			WHERE id = ?");	
			$this->ticketId = htmlspecialchars(strip_tags($this->ticketId));		
			$stmt->bind_param("i", $this->ticketId);
			if($stmt->execute()){
				return true;
			}		
		}		
	}
	
	function mentionUser() {		
		if($_SESSION["userid"] && $this->mentionUser) {
			$stmt = $this->conn->prepare("
			UPDATE ".$this->ticketsTable." 
			SET mentioned = CONCAT(mentioned,',$this->mentionUser')
			WHERE id = ?");	
			$this->mentionTicketId = htmlspecialchars(strip_tags($this->mentionTicketId));		
			$stmt->bind_param("i", $this->mentionTicketId);
			if($stmt->execute()){
				return true;
			}		
		}		
	}
	
	function removeMentionEmail() {		
		if($_SESSION["userid"] && $this->mentionTicketId && $this->mentionEmail) {
			$stmt = $this->conn->prepare("
			UPDATE ".$this->ticketsTable." 
			SET mentioned = REPLACE(mentioned, '".$this->mentionEmail."', '')
			WHERE id = ?");	
			$this->mentionTicketId = htmlspecialchars(strip_tags($this->mentionTicketId));		
			$stmt->bind_param("i", $this->mentionTicketId);
			if($stmt->execute()){
				return true;
			}		
		}		
	}
	
	function getMentionUser() {
		if($this->ticket_id) {
			$sqlQuery = "
				SELECT mentioned 
				FROM ".$this->ticketsTable." 
				WHERE id = ?";			
			$stmt = $this->conn->prepare($sqlQuery);
			$this->ticket_id = htmlspecialchars(strip_tags($this->ticket_id));
			$stmt->bind_param("i", $this->ticket_id);
			$stmt->execute();
			$result = $stmt->get_result();
			return $result;
		}
	}
	
	function getTicketCount() {
		$sqlQuery = "
		SELECT * FROM ".$this->ticketsTable." 
		WHERE status = 'open'";			
		$stmt = $this->conn->prepare($sqlQuery);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->num_rows;
	}
	
	function getUsersTicket() {
		$sqlQuery = "
		SELECT * FROM ".$this->ticketsTable." 
		WHERE status = 'open' AND userid = ?";			
		$stmt = $this->conn->prepare($sqlQuery);
		$stmt->bind_param("i", $_SESSION["userid"]);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->num_rows;
	}
	
	function issueList(){		
		$stmt = $this->conn->prepare("SELECT id, issue, status 
		FROM ".$this->issueTable);				
		$stmt->execute();			
		$result = $stmt->get_result();		
		return $result;	
	}	
	
	function timeElapsedString($datetime, $full = false) {
		$now = new DateTime;
		$ago = new DateTime($datetime);
		$diff = $now->diff($ago);
		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;
		$string = array(
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		);
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
				unset($string[$k]);
			}
		}
		if (!$full) $string = array_slice($string, 0, 1);
		return $string ? implode(', ', $string) . ' ago' : 'just now';
	}
}
?>


/* ticket.js */

$(document).ready(function(){

	var urlPath = window.location.search;
	var tabId = urlPath.split('=').pop();    
	$('#open, #closed, #newest, #oldest').removeClass('active');	
	$('#'+tabId).addClass('active');
	
	$("#ticketForm").submit(function(event){	
		saveTicket();	
		return false;
	});
	
	$('#ticketReplyButton').click(function(){
		var ticketId = $(this).attr("data-ticket-id");		
		$('#ticketReplyModal').on("shown.bs.modal", function () {
			$('#ticketId').val(ticketId);
		}).modal({
			backdrop: 'static',
			keyboard: false
		});		
	});
	
	$("#ticketReplyModal").on('submit','#replyForm', function(event){
		event.preventDefault();
		$('#save').attr('disabled','disabled');
		var formData = $(this).serialize();
		$.ajax({
			url:"action.php",
			method:"POST",
			data:formData,
			success:function(data){				
				$('#replyForm')[0].reset();
				$('#ticketReplyModal').modal('hide');				
				$('#save').attr('disabled', false);	
				location.reload();
			}
		})
	});	
	
	$("#ticketReplyDetails").on('click','#openTicket', function(event){
		var ticketId = $(this).attr("data-ticket-id");
		var action = "openTicket";
		$.ajax({
			url:"action.php",
			method:"POST",
			data:{ticketId:ticketId, action:action},
			success:function(data) {					
				location.reload();
			}
		});
		
	});
	
	$("#ticketReplyDetails").on('click','#closeTicket', function(event){
		var ticketId = $(this).attr("data-ticket-id");
		var action = "closeTicket";
		$.ajax({
			url:"action.php",
			method:"POST",
			data:{ticketId:ticketId, action:action},
			success:function(data) {					
				location.reload();
			}
		});
	});
	
	$('#mentionUser').click(function(){
		var ticketId = $(this).attr("data-ticket-id");		
		$('#mentionModal').on("shown.bs.modal", function () {
			$('#mentionTicketId').val(ticketId);
		}).modal({
			backdrop: 'static',
			keyboard: false
		});		
	});
	
	
	$("#mentionModal").on('submit','#mentionForm', function(event){
		event.preventDefault();
		$('#save').attr('disabled','disabled');
		var formData = $(this).serialize();
		$.ajax({
			url:"action.php",
			method:"POST",
			data:formData,
			success:function(data){				
				$('#mentionForm')[0].reset();
				$('#mentionModal').modal('hide');				
				$('#save').attr('disabled', false);	
				location.reload();
			}
		})
	});	
	
	$('[id^=removeMentionEmail_]').click(function(e){		
		var mentionEmail = $(this).attr('data-mention-email');
		var ticketId = $(this).attr("data-ticket-id");	
		console.log(mentionEmail);
		var action = "removeMentionEmail";
		$.ajax({
			url:"action.php",
			method:"POST",
			data:{mentionTicketId:ticketId, mentionEmail : mentionEmail, action:action},
			success:function(data) {					
				location.reload();
			}
		});
		
	});
	
});


function saveTicket(){
	 $.ajax({
		type: "POST",
		url: "action.php",
		cache:false,
		data: $('form#ticketForm').serialize(),
		success: function(response){			
			$("#newIssue").modal('hide');
			location.reload();
		},
		error: function(){
			alert("Error");
		}
	});
}


/* index.php */

<?php
include_once 'config/Database.php';
include_once 'class/User.php';
include_once 'class/Ticket.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$ticket = new Ticket($db);

if(!$user->loggedIn()) {	
	header("Location: login.php");	
}

include('inc/header.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Home</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>	
<link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"> <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script> 
<script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script> 
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
<script src="js/ticket.js"></script>
<link href="css/style.css" rel="stylesheet">
<div class="container">
<section class="content">
	<div class="row">		
		<?php include('left_navigation.php'); ?>
		<div class="col-md-9">
			<div class="grid support-content">
				 <div class="grid-body">
					 <h2>Tickets</h2>
					 <hr>
					 <div class="btn-group">
						<a href="index.php?status=open">
							<button type="button" id="open" class="btn btn-default active"><?php echo $ticket->getTicketCountWithStatus('open'); ?> Open</button>
						</a>						
					</div>
					<div class="btn-group">
						<a href="index.php?status=closed">
							<button type="button" id="closed" class="btn btn-default"><?php echo $ticket->getTicketCountWithStatus('closed'); ?> Closed</button>
						</a>
					</div>
					<div class="btn-group">
						<a href="index.php?order=newest">
							<button type="button" id="newest" class="btn btn-default">Newest</button>
						</a>
					</div>
					<div class="btn-group">
						<a href="index.php?order=oldest">
							<button type="button" id="oldest" class="btn btn-default">Oldest</button>
						</a>
					</div>
					<button type="button" class="btn btn-success pull-right" data-toggle="modal" data-target="#newIssue">Create Ticket</button>
					<div class="modal fade" id="newIssue" tabindex="-1" role="dialog" aria-labelledby="newIssue" aria-hidden="true">
						<div class="modal-wrapper">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header bg-blue">
										<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
										<h4 class="modal-title"><i class="fa fa-pencil"></i> Create New Ticket</h4>
									</div>
									<form id="ticketForm" method="post">
										<div class="modal-body">
											<div class="form-group">
												<input name="subject" type="text" class="form-control" placeholder="Subject">
											</div>	
											<div class="form-group">
												<select style="height:34px;" class="form-control" id="issue" name="issue">
													<option value=''>Select Issue</option>
													<?php 
													$result = $ticket->issueList();
													while ($issue = $result->fetch_assoc()) { 	
													?>
														<option value="<?php echo $issue['id']; ?>"><?php echo ucfirst($issue['issue']); ?></option>							
													<?php } ?>
												</select>
											</div>	
											<div class="form-group">
												<textarea name="message" class="form-control" placeholder="Please detail your issue or question" style="height: 120px;"></textarea>
											</div>											
										</div>
										<div class="modal-footer">
											<input name="action" type="hidden" value="createTicket">
											<button type="submit" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
											<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-pencil"></i> Create</button>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
					<div class="padding"></div>
					<div class="row">						
						<div class="col-md-12">
							<ul class="list-group fa-padding">
								<?php
								if(isset($_GET['userid']) && !empty($_GET['userid'])) {
									$ticket->userId = $_GET['userid'];
								}
								if(isset($_GET['status']) && !empty($_GET['status'])) {
									$ticket->status = $_GET['status'];
								} else if(isset($_GET['order']) && !empty($_GET['order'])) {
									$ticket->order = $_GET['order'];
								} else if(isset($_GET['mentioned']) && !empty($_GET['mentioned'])) {
									$ticket->mentioned = $_GET['mentioned'];
								}		
								$ticketResult = $ticket->getTicket();
								while ($ticketDetails = $ticketResult->fetch_assoc()) {
									$ticket->id = $ticketDetails["id"];
								?>
								<li class="list-group-item">
									<div class="media">
										<i class="fa fa-code pull-left"></i>
										<div class="media-body">
											<a href="ticket.php?ticket_id=<?php echo $ticketDetails["id"]; ?>"><strong><?php echo $ticketDetails['title']; ?></strong> <span class="number pull-right"># <?php echo $ticketDetails['id']; ?> </span></a>
											<p class="info">Opened by <a href="#"><?php echo $ticketDetails['name']; ?></a> <?php echo $ticket->timeElapsedString($ticketDetails['created']); ?> <i class="fa fa-comments"></i> <a href="#"><?php echo $ticket->getReplyCount(); ?> Reply</a></p>
										</div>
									</div>
								</li>
								<?php
								}
								?>								
							</ul>
						</div>						
					</div>
				</div>
			</div>
		</div>		
	</div>
</section>
</div>
