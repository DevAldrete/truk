<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case ManageCatalog = 'catalog:manage';
    case ManageOperations = 'operations:manage';
    case ExecuteOperations = 'operations:execute';
    case OverrideCapacity = 'capacity:override';
    case OverrideCompliance = 'compliance:override';
    case ManageCompliance = 'compliance:manage';
    case ManageBilling = 'billing:manage';
    case ViewCosts = 'costs:view';
}
