<?php

use Tempest\View\GenericView;

/** @var GenericView $this */
$localVariable = 'fromPHP';
?>

<x-view-defined-local-vars-a :var="$localVariable"></x-view-defined-local-vars-a>
<x-view-defined-local-vars-a var="fromString"></x-view-defined-local-vars-a>
<x-view-defined-local-vars-a/>