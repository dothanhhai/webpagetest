<?php
if(empty($_GET['niteco']) || $_GET['niteco'] != 'wpt') {
    include("index.html");
    die;
}
// Copyright 2020 Catchpoint Systems Inc.
// Use of this source code is governed by the Polyform Shield 1.0.0 license that can be
// found in the LICENSE.md file.
require_once __DIR__ . '/home.php';
