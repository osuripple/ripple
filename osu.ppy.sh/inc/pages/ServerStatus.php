<?php
class ServerStatus {
	const PageID = 27;
	const URL = "status";
	const Title = "Ripple - Server Status";
	const LoggedIn = true;
	public $error_messages = array();
	public $mh_GET = array();
	public $mh_POST = array();

	public function P() {
		global $ServerStatusConfig;
		if (!$ServerStatusConfig["enable"])
		{
			echo('
			<div id="content-wide">
				<div align="center">
					<h1><i class="fa fa-cogs"></i> Server status</h1>
					<b>Unfortunately, no server status for this ripple instance is available. Slap the sysadmin off telling him to configure it.</b>
				</div>
			</div>');
		}
		else
			echo('
				<div id="content-wide">
				<div align="center">
					<h1><i class="fa fa-check-circle"></i> Services status</h1>
					<table class="table table-striped table-hover" style="width:50%">
						<thead>
							<tr>
								<th class="text-center">Service</th>
								<th class="text-center">Status</th>
							</tr>
						</thead>
						<tbody>
							<tr><td><p class="text-center">Website</p></td><td><p class="text-center">' . serverStatusBadge(1) . '</p></td></tr>
							<tr><td><p class="text-center">Bancho</p></td><td><p class="text-center">' . serverStatusBadge(checkServiceStatus($ServerStatusConfig["bancho_url"] . "/api/server-status")) . '</p></td></tr>
							<tr><td><p class="text-center">Avatars</p></td><td><p class="text-center">' . serverStatusBadge(checkServiceStatus($ServerStatusConfig["avatars_url"] . "/status")) . '</p></td></tr>
							<tr><td><p class="text-center">Beatmaps</p></td><td><p class="text-center">' . serverStatusBadge(checkServiceStatus($ServerStatusConfig["beatmap_url"] . "/status.json")) . '</p></td></tr>
						</tbody>
					</table>
				</div>
				<br><br>
				<div>
					<h1><i class="fa fa-server"></i> Server info</h1>
					<div data-netdata="system.swap" data-dimensions="free" data-append-options="percentage" data-chart-library="easypiechart" data-title="Free Swap" data-units="%" data-easypiechart-max-value="100" data-width="12%" data-before="0" data-after="-300" data-points="300"></div>
					<div data-netdata="system.io" data-chart-library="easypiechart" data-title="Disk usage" data-units="KB / s" data-width="15%" data-before="0" data-after="-300" data-points="300"></div>
					<div data-netdata="system.cpu" data-title="CPU usage" data-method="max" data-gauge-max-value="100" data-units="%" data-width="20%" data-chart-library="gauge"></div>
					<div data-netdata="system.ram" data-dimensions="cached|free" data-append-options="percentage" data-chart-library="easypiechart" data-title="Available RAM" data-units="%" data-easypiechart-max-value="100" data-width="15%" data-after="-300" data-points="300"></div>
					<div data-netdata="system.ipv4" data-dimensions="received" data-units="kbps" data-title="IPv4 usage" data-width="12%" data-chart-library="easypiechart" ></div>

					<div style="height:70px"></div>
					<h3><i class="fa fa-cogs"></i> System</h3>
					<div data-netdata="system.cpu" data-title="CPU usage" data-method="max" data-width="100%" data-height="200px"></div>
					<div data-netdata="system.load" data-title="System load" data-width="100%" data-height="200px"></div>
					<div data-netdata="system.ram" data-dimensions="used" data-title="Used RAM" data-width="100%" data-height="200px"></div>
					<div style="height:70px"></div>
					<h3><i class="fa fa-upload"></i> Network</h3>
					<div data-netdata="system.ipv4" data-title="IPv4 traffic" data-width="100%" data-height="200px"></div>
					<div data-netdata="ipv4.tcpsock" data-title="IPv4 TCP connections" data-width="100%" data-height="200px"></div>
					<div data-netdata="ipv4.tcppackets" data-title="IPv4 TCP packets" data-width="100%" data-height="200px"></div>
					<div style="height:70px"></div>
					<h3><i class="fa fa-hdd-o"></i> Disk</h3>
					<div data-netdata="disk.vda" data-title="Disk I/O Bandwidth" data-width="100%" data-height="200px"></div>
					<div style="height:70px"></div>
					<h3><i class="fa fa-database"></i> MySQL</h3>
					<div data-netdata="mysql_srv.net" data-title="MySQL Bandwidth" data-width="100%" data-height="200px"></div>
					<div data-netdata="mysql_srv.queries" data-title="MySQL queries" data-width="100%" data-height="200px"></div>
					<div style="height:70px"></div>
					<h3><i class="fa fa-globe"></i> Nginx</h3>
					<div data-netdata="nginx.connections" data-title="Nginx active connections" data-width="100%" data-height="200px"></div>
					<div data-netdata="nginx.requests" data-title="Nginx requests/second" data-width="100%" data-height="200px"></div>
					<div data-netdata="nginx.connections_status" data-title="Nginx connections status" data-width="100%" data-height="200px"></div>
				</div>
			');
	}
}
