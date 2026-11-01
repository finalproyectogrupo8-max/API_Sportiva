<?php

namespace App\Club\Alerts;

use App\Club\Alerts\Rules\AttendanceAlertRule;
use App\Club\Alerts\Rules\CallupPendingAlertRule;
use App\Club\Alerts\Rules\RegistrationClosingAlertRule;
use App\Club\Alerts\Rules\RequirementExpiringAlertRule;
use App\Models\Tenant;

/**
 * Orquestador de alertas automáticas del recorrido del partido para un club (TIZ-27).
 */
class AlertRunner
{
    public function __construct(
        private AttendanceAlertRule $attendanceRule,
        private RequirementExpiringAlertRule $requirementRule,
        private RegistrationClosingAlertRule $registrationRule,
        private CallupPendingAlertRule $callupRule,
    ) {}

    public function run(Tenant $tenant, bool $dryRun = false): int
    {
        $count = 0;

        $count += $this->attendanceRule->evaluate($dryRun);
        $count += $this->requirementRule->evaluate($dryRun);
        $count += $this->registrationRule->evaluate($dryRun);
        $count += $this->callupRule->evaluate($dryRun);

        return $count;
    }
}
