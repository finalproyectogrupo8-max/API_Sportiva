<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

abstract class BaseMailable extends Mailable implements ShouldQueue {}
