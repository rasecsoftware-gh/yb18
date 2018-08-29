<?php
	require_once 'sys.php';
	class Session {
		public static function RegisterLogIn() {
			$_SESSION['sys_user_logkey'] = self::GenKey();
			$dr = new SqlDataRow('dbo.usuario_log');
			$dr->Create();
			$last = $dr->GetMax('c_usulog', NULL, 0);
			$next = str_pad($last+1, 10, '0', STR_PAD_LEFT);
			$dr->Set('c_usulog', $next);
			$dr->Set('c_usuario', strtoupper(Sys::GetUserId()));
			$dr->Set('d_usulog', date('d/m/Y H:i:s'));
			$dr->Set('n_usulog_codi', $_SESSION['sys_user_logkey']);
			$dr->Set('f_usulog_tipo', '01');
			$dr->Set('n_usulog_ip', Sys::GetUserIP());
			$dr->Update();
		}
		public static function RegisterLogOut() {
			$dr = new SqlDataRow('dbo.usuario_log');
			$dr->Create();
			$last = $dr->GetMax('c_usulog', NULL, 0);
			$next = str_pad($last+1, 10, '0', STR_PAD_LEFT);
			$dr->Set('c_usulog', $next);
			$dr->Set('c_usuario', strtoupper(Sys::GetUserId()));
			$dr->Set('d_usulog', date('d/m/Y H:i:s'));
			$dr->Set('n_usulog_codi', $_SESSION['sys_user_logkey']);
			$dr->Set('f_usulog_tipo', '00');
			$dr->Set('n_usulog_ip', Sys::GetUserIP());
			$dr->Update();
		}
		public static function RegisterLogFailed($pwd) {
			$dr = new SqlDataRow('dbo.usuario_log');
			$dr->Create();
			$last = $dr->GetMax('c_usulog', NULL, 0);
			$next = str_pad($last+1, 10, '0', STR_PAD_LEFT);
			$dr->Set('c_usulog', $next);
			$dr->Set('c_usuario', strtoupper(Sys::GetUserId()));
			$dr->Set('d_usulog', date('d/m/Y H:i:s'));
			$dr->Set('n_usulog_codi', $pwd);
			$dr->Set('f_usulog_tipo', '99');
			$dr->Set('n_usulog_ip', Sys::GetUserIP());
			$dr->Update();
		}
		public static function GenKey() {
			$r = range('A', 'Z');
			$t = microtime();
			$k = $r[rand(0, 25)].$t;
	        return md5($k);
		}
	}
?>