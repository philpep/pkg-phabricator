ALTER TABLE {$NAMESPACE}_daemon.daemon_log
  ADD envInfo LONGTEXT NOT NULL COLLATE {$COLLATE_TEXT};
