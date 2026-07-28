package com.example.wcc_companion_app.ui.shell

import android.app.Activity
import androidx.activity.compose.BackHandler
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.compose.ui.graphics.Color
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.wcc_companion_app.R
import com.example.wcc_companion_app.data.remote.WccApiService
import com.example.wcc_companion_app.data.repository.AuthRepository
import com.example.wcc_companion_app.data.sync.SyncCoordinator
import com.example.wcc_companion_app.ui.components.MmmCategory
import com.example.wcc_companion_app.ui.components.MmmLayout
import com.example.wcc_companion_app.ui.tickets.TicketViewModel
import kotlinx.coroutines.launch

/**
 * Signed-in floor shell: MMM chrome, rails, top bar, overlay host.
 * Extracted from MainActivity so the activity only owns process entry + login nav.
 */
@Composable
fun AppShell(
    isDark: Boolean,
    onToggleTheme: () -> Unit = {},
    appLocale: com.example.wcc_companion_app.data.locale.AppLocale =
        com.example.wcc_companion_app.data.locale.AppLocale.ENGLISH,
    onLocaleSelected: (com.example.wcc_companion_app.data.locale.AppLocale) -> Unit = {},
    authRepository: AuthRepository,
    apiService: WccApiService,
    syncCoordinator: com.example.wcc_companion_app.data.sync.SyncCoordinator,
    onLogout: () -> Unit = {},
    ticketViewModel: TicketViewModel = hiltViewModel(),
    workOrderViewModel: com.example.wcc_companion_app.ui.workorders.WorkOrderViewModel = hiltViewModel(),
    equipmentViewModel: com.example.wcc_companion_app.ui.equipment.EquipmentViewModel = hiltViewModel(),
    inventoryViewModel: com.example.wcc_companion_app.ui.inventory.InventoryViewModel = hiltViewModel(),
    historyViewModel: com.example.wcc_companion_app.ui.history.HistoryViewModel = hiltViewModel()
) {
    val tickets by ticketViewModel.tickets.collectAsState()
    val workOrders by workOrderViewModel.workOrders.collectAsState()
    val equipment by equipmentViewModel.equipment.collectAsState()
    val parts by inventoryViewModel.parts.collectAsState()
    val historyItems by historyViewModel.items.collectAsState()

    // Rail filters â€” Equipment / Toolings / Inventory (compact strip â†’ setup sheet)
    var equipQuery by remember { mutableStateOf("") }
    var equipCritChip by remember { mutableStateOf<String?>(null) } // A | B | C
    var toolingQuery by remember { mutableStateOf("") }
    var invQuery by remember { mutableStateOf("") }
    var invStockChip by remember { mutableStateOf<String?>(null) } // healthy|low|reorder|out
    var ticketsMineOnly by remember { mutableStateOf(false) }
    var workOrdersMineOnly by remember { mutableStateOf(false) }
    var toolings by remember {
        mutableStateOf<List<com.example.wcc_companion_app.data.remote.models.ToolingDto>>(emptyList())
    }
    var toolingsTableOnline by remember { mutableStateOf(false) }

    // No criteria â‡’ every item matches (show the full rail). Only narrow when the
    // user typed a query and/or picked a chip.
    val filteredEquipment = remember(equipment, equipQuery, equipCritChip) {
        val q = equipQuery.trim()
        if (q.isEmpty() && equipCritChip == null) {
            equipment
        } else {
            equipment.filter { e ->
                val critOk = equipCritChip == null ||
                    e.criticality.equals(equipCritChip, ignoreCase = true)
                val textOk = q.isEmpty() ||
                    e.equip_name.contains(q, true) ||
                    (e.asset_uuid?.contains(q, true) == true) ||
                    (e.category?.contains(q, true) == true) ||
                    (e.equipment_type?.contains(q, true) == true) ||
                    (e.oem_brand?.contains(q, true) == true) ||
                    (e.oem_model?.contains(q, true) == true) ||
                    (e.oem_serial?.contains(q, true) == true) ||
                    (e.plant_name?.contains(q, true) == true) ||
                    (e.line_name?.contains(q, true) == true) ||
                    (e.station_name?.contains(q, true) == true)
                critOk && textOk
            }
        }
    }
    val filteredParts = remember(parts, invQuery, invStockChip) {
        val q = invQuery.trim()
        if (q.isEmpty() && invStockChip == null) {
            parts
        } else {
            parts.filter { p ->
                val stock = com.example.wcc_companion_app.ui.inventory.classifyStock(p)
                val chipOk = when (invStockChip) {
                    "healthy" -> stock == com.example.wcc_companion_app.ui.inventory.StockState.HEALTHY
                    "low" -> stock == com.example.wcc_companion_app.ui.inventory.StockState.APPROACHING
                    "reorder" -> stock == com.example.wcc_companion_app.ui.inventory.StockState.REORDER ||
                        stock == com.example.wcc_companion_app.ui.inventory.StockState.NO_VENDOR
                    "out" -> stock == com.example.wcc_companion_app.ui.inventory.StockState.OUT
                    "obsolete" -> stock == com.example.wcc_companion_app.ui.inventory.StockState.OBSOLETE
                    else -> true
                }
                val textOk = q.isEmpty() ||
                    p.part_name.contains(q, true) ||
                    p.internal_code.contains(q, true) ||
                    (p.oem_part_number?.contains(q, true) == true) ||
                    (p.bin_code?.contains(q, true) == true) ||
                    (p.aisle?.contains(q, true) == true)
                chipOk && textOk
            }
        }
    }
    val filteredToolings = remember(toolings, toolingQuery) {
        val q = toolingQuery.trim()
        if (q.isEmpty()) {
            toolings
        } else {
            toolings.filter { t ->
                (t.tooling_name?.contains(q, true) == true) ||
                    (t.tooling_code?.contains(q, true) == true) ||
                    (t.barcode?.contains(q, true) == true) ||
                    (t.asset_tag?.contains(q, true) == true) ||
                    (t.category?.contains(q, true) == true) ||
                    (t.location?.contains(q, true) == true)
            }
        }
    }

    // Shell: single overlay back-stack owner (tickets / WO / scan / details / outbox).
    val overlays = remember { com.example.wcc_companion_app.ui.shell.FloorOverlayState() }

    val snackbarHostState = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()

    val mmmNav = com.example.wcc_companion_app.ui.components.rememberMmmNavController()

    val profileViewModel: com.example.wcc_companion_app.ui.profile.ProfileViewModel = hiltViewModel()
    val liveBadge by syncCoordinator.badgeState.collectAsState()
    val profileData by profileViewModel.userProfile.collectAsState()

    // Identity for Mine filter / My Shift (profile first, then offline cache).
    val myUserName = remember(profileData) {
        profileData?.get("username")?.toString()?.takeIf { it.isNotBlank() }
            ?: authRepository.getCachedUsername()
    }
    val myFullName = remember(profileData) {
        profileData?.get("name")?.toString()?.takeIf { it.isNotBlank() }
            ?: profileData?.get("full_name")?.toString()?.takeIf { it.isNotBlank() }
            ?: authRepository.getCachedFullName()
    }
    val myUserId = remember(profileData) {
        (profileData?.get("user_id") as? Number)?.toInt()
            ?: (profileData?.get("id") as? Number)?.toInt()
            ?: authRepository.getCachedUserId()
    }
    val myIdentities = remember(myUserName, myFullName) {
        listOfNotNull(myUserName, myFullName)
            .map { it.trim().lowercase() }
            .filter { it.isNotEmpty() }
            .toSet()
    }
    val myTickets = remember(tickets, myIdentities) {
        tickets.filter { t ->
            val pic = t.pic?.trim()?.lowercase() ?: return@filter false
            pic in myIdentities
        }
    }
    val myWorkOrders = remember(workOrders, myUserId) {
        if (myUserId == null) emptyList()
        else workOrders.filter { wo ->
            val st = wo.status ?: ""
            val active = st.equals("In Progress", true) ||
                st.equals("Missed", true) ||
                st.equals("Scheduled", true)
            active && wo.assigned_to == myUserId
        }
    }
    val railTickets = remember(tickets, ticketsMineOnly, myTickets) {
        if (ticketsMineOnly) myTickets else tickets
    }
    val railWorkOrders = remember(workOrders, workOrdersMineOnly, myWorkOrders) {
        if (workOrdersMineOnly) myWorkOrders else workOrders
    }

    var factoryHealth by remember {
        mutableStateOf<com.example.wcc_companion_app.data.remote.models.FactoryHealthDto?>(null)
    }
    var factoryHealthStale by remember { mutableStateOf(false) }

    val toolingPlaceholder = remember {
        listOf(com.example.wcc_companion_app.ui.toolings.ToolingPlaceholder())
    }

    suspend fun loadToolingsRail() {
        try {
            val resp = apiService.searchToolings(search = null)
            if (resp.isSuccessful) {
                toolings = resp.body()?.data.orEmpty()
                toolingsTableOnline = toolings.isNotEmpty()
            } else {
                // Keep last local list if pull fails
                if (toolings.isEmpty()) toolingsTableOnline = false
            }
        } catch (_: Exception) {
            if (toolings.isEmpty()) toolingsTableOnline = false
        }
    }

    suspend fun loadFactoryHealth() {
        try {
            val resp = apiService.getFactoryHealth()
            val body = resp.body()
            if (resp.isSuccessful && body?.status == "success" && body.data != null) {
                factoryHealth = body.data
                factoryHealthStale = false
            } else if (factoryHealth != null) {
                factoryHealthStale = true
            }
        } catch (_: Exception) {
            if (factoryHealth != null) factoryHealthStale = true
        }
    }

    LaunchedEffect(Unit) {
        syncCoordinator.start()
        val creds = authRepository.getCredentials()
        if (creds != null) {
            try {
                apiService.loginForm(creds.first, creds.second)
            } catch (_: Exception) { /* offline boot with cache */ }
            // Full sync: tickets + outbox drain + equip/parts/toolings
            val ok = syncCoordinator.syncNow(pullReferences = true)
            ticketViewModel.refreshReferenceData()
            workOrderViewModel.loadWorkOrders()
            historyViewModel.loadHistory()
            loadToolingsRail()
            loadFactoryHealth()
            if (!ok && toolings.isEmpty()) {
                // stay on local room data for tickets/equip/parts via ViewModels
            }
        }
    }

    // Soft poll plant health while signed in (foreground).
    LaunchedEffect(Unit) {
        while (true) {
            kotlinx.coroutines.delay(75_000)
            if (authRepository.getCredentials() != null) {
                loadFactoryHealth()
            }
        }
    }

    val resync: () -> Unit = {
        scope.launch {
            val ok = syncCoordinator.syncNow(pullReferences = true)
            workOrderViewModel.loadWorkOrders()
            historyViewModel.loadHistory()
            loadToolingsRail()
            loadFactoryHealth()
            snackbarHostState.showSnackbar(
                if (ok) "Workshop data refreshed"
                else "Offline â€” server unreachable"
            )
        }
    }

    // Failed outbox drain â†’ snackbar with Retry (also opens sheet for detail).
    LaunchedEffect(Unit) {
        syncCoordinator.syncEvents.collect { event ->
            when (event) {
                is com.example.wcc_companion_app.data.sync.SyncUiEvent.Failed -> {
                    val result = snackbarHostState.showSnackbar(
                        message = event.message,
                        actionLabel = if (event.pendingCount > 0) "Retry" else null,
                        duration = SnackbarDuration.Long,
                    )
                    if (result == SnackbarResult.ActionPerformed) {
                        overlays.showOutboxSheet = true
                        resync()
                    }
                }
            }
        }
    }

    fun openTicketFromScan(t: com.example.wcc_companion_app.data.remote.models.TicketDto) {
        mmmNav.focusCategory("tickets")
        overlays.openTicketByStatus(t)
    }

    fun openWorkOrderFromScan(wo: com.example.wcc_companion_app.data.remote.models.WorkOrderDto) {
        mmmNav.focusCategory("work_orders")
        overlays.activeWorkOrder = wo
    }

    val handleLogout: () -> Unit = {
        scope.launch {
            syncCoordinator.clearLocalData()
            authRepository.clearCachedUserIdentity()
            onLogout()
        }
    }

    // Rail order locked: Tickets â†’ WO â†’ Equipment â†’ Toolings â†’ Inventory â†’ History
    val categories = listOf(
        MmmCategory(
            id = "tickets",
            title = androidx.compose.ui.res.stringResource(R.string.rail_tickets),
            icon = Icons.Default.ConfirmationNumber,
            description = androidx.compose.ui.res.stringResource(R.string.rail_tickets_desc),
            color = Color(0xFFFF1744)
        ),
        MmmCategory(
            id = "work_orders",
            title = androidx.compose.ui.res.stringResource(R.string.rail_work_orders),
            icon = Icons.Default.Build,
            description = androidx.compose.ui.res.stringResource(R.string.rail_work_orders_desc),
            color = Color(0xFFFF9100)
        ),
        MmmCategory(
            id = "equipment",
            title = androidx.compose.ui.res.stringResource(R.string.rail_equipment),
            icon = Icons.Default.Settings,
            description = androidx.compose.ui.res.stringResource(R.string.rail_equipment_desc),
            color = Color(0xFFD500F9)
        ),
        MmmCategory(
            id = "toolings",
            title = androidx.compose.ui.res.stringResource(R.string.rail_toolings),
            icon = Icons.Default.Construction,
            description = androidx.compose.ui.res.stringResource(R.string.rail_toolings_desc),
            color = Color(0xFF7C4DFF)
        ),
        MmmCategory(
            id = "inventory",
            title = androidx.compose.ui.res.stringResource(R.string.rail_inventory),
            icon = Icons.Default.Inventory2,
            description = androidx.compose.ui.res.stringResource(R.string.rail_inventory_desc),
            color = Color(0xFF00E5FF)
        ),
        MmmCategory(
            id = "history",
            title = androidx.compose.ui.res.stringResource(R.string.rail_history),
            icon = Icons.Default.History,
            description = androidx.compose.ui.res.stringResource(R.string.rail_history_desc),
            color = Color(0xFF1DE9B6)
        )
    )

    var showQuitDialog by remember { mutableStateOf(false) }
    val activity = LocalContext.current as? Activity
    var showBetaDisclaimer by remember {
        mutableStateOf(!authRepository.isBetaDisclaimerAccepted())
    }
    var showBetaBanner by remember {
        mutableStateOf(!authRepository.isBetaBannerDismissed())
    }

    if (showBetaDisclaimer) {
        com.example.wcc_companion_app.ui.components.OpenBetaDisclaimerDialog(
            onAccept = {
                authRepository.setBetaDisclaimerAccepted(true)
                showBetaDisclaimer = false
            }
        )
    }

    BackHandler(enabled = overlays.anyOverlay) {
        overlays.popTop()
    }

    // Root back (category rail, no overlay): MmmLayout's BackHandler is off at CATEGORY,
    // so this asks before finishing the Activity.
    BackHandler(enabled = !overlays.anyOverlay) {
        showQuitDialog = true
    }

    if (showQuitDialog) {
        AlertDialog(
            onDismissRequest = { showQuitDialog = false },
            title = { Text("Quit WCC Companion?") },
            text = {
                Text("Are you sure you want to leave the floor app?")
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        showQuitDialog = false
                        activity?.finish()
                    }
                ) {
                    Text("Quit")
                }
            },
            dismissButton = {
                TextButton(onClick = { showQuitDialog = false }) {
                    Text("Stay")
                }
            }
        )
    }

    Box {
        MmmLayout(
            isDark = isDark,
            categories = categories,
            itemsProvider = { category ->
                when (category.id) {
                    "tickets" -> railTickets
                    "work_orders" -> railWorkOrders
                    "equipment" -> filteredEquipment // empty query+chip â‡’ full list
                    "toolings" -> if (toolingsTableOnline) {
                        filteredToolings // empty query â‡’ full list; may be empty if filtered out
                    } else {
                        toolingPlaceholder
                    }
                    "inventory" -> filteredParts // empty query+chip â‡’ full list
                    "history" -> historyItems
                    else -> emptyList()
                }
            },
            hasItemFilterBar = { category ->
                category.id == "equipment" ||
                    category.id == "toolings" ||
                    category.id == "inventory" ||
                    category.id == "tickets" ||
                    category.id == "work_orders"
            },
            itemFilterBar = { category ->
                when (category.id) {
                    "tickets" -> com.example.wcc_companion_app.ui.components.MineFilterStrip(
                        mineOnly = ticketsMineOnly,
                        mineCount = myTickets.size,
                        totalCount = tickets.size,
                        onMineOnlyChange = { ticketsMineOnly = it }
                    )
                    "work_orders" -> com.example.wcc_companion_app.ui.components.MineFilterStrip(
                        mineOnly = workOrdersMineOnly,
                        mineCount = myWorkOrders.size,
                        totalCount = workOrders.size,
                        onMineOnlyChange = { workOrdersMineOnly = it }
                    )
                    "equipment" -> com.example.wcc_companion_app.ui.components.RailFilterStrip(
                        domain = com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT,
                        query = equipQuery,
                        chipId = equipCritChip,
                        matchCount = filteredEquipment.size,
                        totalCount = equipment.size,
                        onOpenSetup = {
                            overlays.filterSheetDomain =
                                com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT
                        },
                        onClear = {
                            equipQuery = ""
                            equipCritChip = null
                        }
                    )
                    "toolings" -> com.example.wcc_companion_app.ui.components.RailFilterStrip(
                        domain = com.example.wcc_companion_app.ui.components.RailFilterDomain.TOOLINGS,
                        query = toolingQuery,
                        chipId = null,
                        matchCount = if (toolingsTableOnline) filteredToolings.size else 0,
                        totalCount = if (toolingsTableOnline) toolings.size else 0,
                        onOpenSetup = {
                            overlays.filterSheetDomain =
                                com.example.wcc_companion_app.ui.components.RailFilterDomain.TOOLINGS
                        },
                        onClear = { toolingQuery = "" }
                    )
                    "inventory" -> com.example.wcc_companion_app.ui.components.RailFilterStrip(
                        domain = com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY,
                        query = invQuery,
                        chipId = invStockChip,
                        matchCount = filteredParts.size,
                        totalCount = parts.size,
                        onOpenSetup = {
                            overlays.filterSheetDomain =
                                com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY
                        },
                        onClear = {
                            invQuery = ""
                            invStockChip = null
                        }
                    )
                    else -> { /* tickets / WO / history â€” no rail filter */ }
                }
            },
            // Six independent rail containers (see ui/rails/RailContainers.kt)
            itemContent = { item, isFocused ->
                when (item) {
                    is com.example.wcc_companion_app.data.remote.models.TicketDto -> {
                        com.example.wcc_companion_app.ui.rails.TicketRailContainer(
                            ticket = item,
                            isFocused = isFocused,
                            onTakeover = { overlays.takeoverTicket = it },
                            onCloseout = { overlays.closeoutTicket = it },
                            onHold = { overlays.holdTicket = it }
                        )
                    }
                    is com.example.wcc_companion_app.data.remote.models.WorkOrderDto -> {
                        com.example.wcc_companion_app.ui.rails.WorkOrderRailContainer(
                            wo = item,
                            isFocused = isFocused,
                            onTakeover = { overlays.activeWorkOrder = it }
                        )
                    }
                    is com.example.wcc_companion_app.data.remote.models.EquipmentDto -> {
                        com.example.wcc_companion_app.ui.rails.EquipmentRailContainer(
                            equipment = item,
                            isFocused = isFocused,
                            onOpen = { overlays.selectedEquipment = it },
                            onScan = {
                                overlays.scanStatus = null
                                overlays.showEquipScanner = true
                            }
                        )
                    }
                    is com.example.wcc_companion_app.data.remote.models.ToolingDto -> {
                        com.example.wcc_companion_app.ui.rails.ToolingRailContainer(
                            tooling = item,
                            isFocused = isFocused,
                            onOpen = { overlays.selectedTooling = it }
                        )
                    }
                    is com.example.wcc_companion_app.ui.toolings.ToolingPlaceholder -> {
                        com.example.wcc_companion_app.ui.rails.ToolingRailContainer(
                            tooling = null,
                            isFocused = isFocused
                        )
                    }
                    is com.example.wcc_companion_app.data.remote.models.InventoryPartDto -> {
                        com.example.wcc_companion_app.ui.rails.InventoryRailContainer(
                            part = item,
                            isFocused = isFocused,
                            onOpen = { overlays.selectedPart = it }
                        )
                    }
                    is com.example.wcc_companion_app.ui.history.HistoryRailItem -> {
                        com.example.wcc_companion_app.ui.rails.HistoryRailContainer(
                            item = item,
                            isFocused = isFocused,
                            onFilter = { historyViewModel.setFilter(it) },
                            onOpenEvent = { overlays.selectedHistoryEvent = it },
                            onOpenWo = { overlays.selectedHistoryWo = it }
                        )
                    }
                }
            },
            onItemSelected = { item ->
                when (item) {
                    is com.example.wcc_companion_app.data.remote.models.TicketDto ->
                        overlays.selectedTicket = item
                    is com.example.wcc_companion_app.data.remote.models.WorkOrderDto ->
                        overlays.activeWorkOrder = item
                    is com.example.wcc_companion_app.data.remote.models.EquipmentDto ->
                        overlays.selectedEquipment = item
                    is com.example.wcc_companion_app.data.remote.models.ToolingDto ->
                        overlays.selectedTooling = item
                    is com.example.wcc_companion_app.data.remote.models.InventoryPartDto ->
                        overlays.selectedPart = item
                    is com.example.wcc_companion_app.ui.history.HistoryRailItem.Event ->
                        overlays.selectedHistoryEvent = item.ticket
                    is com.example.wcc_companion_app.ui.history.HistoryRailItem.WorkOrder ->
                        overlays.selectedHistoryWo = item.wo
                }
            },
            onRefresh = resync,
            onLogout = handleLogout,
            snackbarHostState = snackbarHostState,
            navController = mmmNav,
            profileViewModel = profileViewModel,
            initialCategoryId = authRepository.getLastRailCategoryId(),
            onCategorySettled = { id -> authRepository.setLastRailCategoryId(id) },
            myShiftContent = { close ->
                // Identity from the same ProfileViewModel / GET me as Profile.
                com.example.wcc_companion_app.ui.panels.MyShiftPanel(
                    isDark = isDark,
                    tickets = tickets,
                    workOrders = workOrders,
                    onOpenTicket = { overlays.selectedTicket = it },
                    onOpenWorkOrder = { overlays.activeWorkOrder = it },
                    onClose = close,
                    profileViewModel = profileViewModel
                )
            },
            searchContent = { close ->
                com.example.wcc_companion_app.ui.panels.SearchPanel(
                    isDark = isDark,
                    tickets = tickets,
                    workOrders = workOrders,
                    apiService = apiService,
                    onOpenTicket = { openTicketFromScan(it) },
                    onOpenEquipment = {
                        mmmNav.focusCategory("equipment")
                        overlays.selectedEquipment = it
                    },
                    onOpenPart = {
                        mmmNav.focusCategory("inventory")
                        overlays.selectedPart = it
                    },
                    onOpenWorkOrder = { openWorkOrderFromScan(it) },
                    onClose = close
                )
            }
        )

        if (!mmmNav.currentDepth.isPanel) {
            val showBanner = showBetaBanner && !showBetaDisclaimer
            Column(
                modifier = Modifier
                    .align(Alignment.TopCenter)
                    .fillMaxWidth()
                    .statusBarsPadding()
                    .displayCutoutPadding()
            ) {
                if (showBanner) {
                    com.example.wcc_companion_app.ui.components.OpenBetaBanner(
                        onDismiss = {
                            authRepository.setBetaBannerDismissed(true)
                            showBetaBanner = false
                        }
                    )
                }
                com.example.wcc_companion_app.ui.components.WccTopBar(
                    isDark = isDark,
                    liveBadge = liveBadge,
                    factoryHealth = factoryHealth,
                    factoryHealthStale = factoryHealthStale,
                    currentLocale = appLocale,
                    onToggleTheme = onToggleTheme,
                    onOpenProfile = {
                        mmmNav.open(com.example.wcc_companion_app.ui.components.NavDepth.PROFILE)
                    },
                    onResync = resync,
                    onLiveBadgeClick = {
                        when (val b = liveBadge) {
                            is com.example.wcc_companion_app.data.sync.LiveBadgeState.Syncing ->
                                if (b.remaining > 0) overlays.showOutboxSheet = true else resync()
                            is com.example.wcc_companion_app.data.sync.LiveBadgeState.OfflineUnsynced,
                            is com.example.wcc_companion_app.data.sync.LiveBadgeState.Conflict ->
                                overlays.showOutboxSheet = true
                            else -> resync()
                        }
                    },
                    onLocaleSelected = onLocaleSelected,
                    applyStatusBars = false,
                )
            }
        }

        if (overlays.showOutboxSheet) {
            com.example.wcc_companion_app.ui.sync.OutboxSheet(
                onDismiss = { overlays.showOutboxSheet = false },
                onRetry = {
                    resync()
                }
            )
        }

        overlays.selectedTicket?.let { ticket ->
            com.example.wcc_companion_app.ui.tickets.TicketDetailScreen(
                ticket = ticket,
                onClose = { overlays.selectedTicket = null }
            )
        }

        overlays.takeoverTicket?.let { ticket ->
            com.example.wcc_companion_app.ui.tickets.TicketTakeoverScreen(
                ticket = ticket,
                onDismiss = { overlays.takeoverTicket = null },
                onComplete = {
                    overlays.takeoverTicket = null
                    ticketViewModel.loadTickets()
                    scope.launch { snackbarHostState.showSnackbar("Intervention logged successfully") }
                }
            )
        }

        overlays.closeoutTicket?.let { ticket ->
            com.example.wcc_companion_app.ui.tickets.TicketCloseoutScreen(
                ticket = ticket,
                onDismiss = { overlays.closeoutTicket = null },
                onComplete = {
                    overlays.closeoutTicket = null
                    ticketViewModel.loadTickets()
                    // History is a separate feed â€” must refresh or closed events never appear.
                    historyViewModel.loadHistory()
                    scope.launch { snackbarHostState.showSnackbar("Ticket closed and archived") }
                }
            )
        }

        overlays.holdTicket?.let { ticket ->
            com.example.wcc_companion_app.ui.tickets.TicketHoldScreen(
                ticket = ticket,
                onDismiss = { overlays.holdTicket = null },
                onComplete = {
                    overlays.holdTicket = null
                    ticketViewModel.loadTickets()
                    scope.launch { snackbarHostState.showSnackbar("Ticket placed on hold") }
                }
            )
        }

        overlays.selectedEquipment?.let { eq ->
            val openOnAsset = tickets.filter {
                it.equip_id == eq.equip_id && !it.status.equals("CLOSED", ignoreCase = true)
            }
            com.example.wcc_companion_app.ui.equipment.EquipmentDetailScreen(
                equipment = eq,
                openTickets = openOnAsset,
                onClose = { overlays.selectedEquipment = null },
                onOpenTicket = {
                    overlays.selectedEquipment = null
                    overlays.selectedTicket = it
                }
            )
        }

        overlays.selectedTooling?.let { tooling ->
            com.example.wcc_companion_app.ui.toolings.ToolingDetailScreen(
                tooling = tooling,
                onClose = { overlays.selectedTooling = null }
            )
        }

        overlays.selectedPart?.let { part ->
            com.example.wcc_companion_app.ui.inventory.InventoryDetailScreen(
                part = part,
                onClose = { overlays.selectedPart = null }
            )
        }

        overlays.selectedHistoryEvent?.let { ticket ->
            com.example.wcc_companion_app.ui.history.HistoryEventDetailScreen(
                ticket = ticket,
                onClose = { overlays.selectedHistoryEvent = null }
            )
        }

        overlays.selectedHistoryWo?.let { wo ->
            com.example.wcc_companion_app.ui.history.HistoryWoDetailScreen(
                wo = wo,
                onClose = { overlays.selectedHistoryWo = null }
            )
        }

        overlays.activeWorkOrder?.let { wo ->
            com.example.wcc_companion_app.ui.workorders.WorkOrderExecutionScreen(
                wo = wo,
                onDismiss = { overlays.activeWorkOrder = null },
                onCompleted = {
                    overlays.activeWorkOrder = null
                    workOrderViewModel.loadWorkOrders()
                    scope.launch { snackbarHostState.showSnackbar("WO-${wo.wo_id} completed") }
                }
            )
        }

        if (overlays.showEquipScanner) {
            com.example.wcc_companion_app.ui.scan.ScannerScreen(
                title = "Scan QR or DataMatrix",
                statusMessage = overlays.scanStatus,
                isLookingUp = overlays.isLookingUp,
                onClose = { overlays.showEquipScanner = false },
                onCodeScanned = { code ->
                    if (!overlays.isLookingUp) {
                        scope.launch {
                            overlays.isLookingUp = true
                            overlays.scanStatus = "Looking up $code..."
                            try {
                                val resp = apiService.scanLookup(code)
                                val body = resp.body()
                                if (resp.isSuccessful && body?.status == "success" && body.data != null) {
                                    overlays.showEquipScanner = false
                                    overlays.scanStatus = null
                                    overlays.scanResult = body.data
                                } else {
                                    // Fallback: local equipment cache only
                                    val hit = equipmentViewModel.findByAssetTag(code)
                                    if (hit != null) {
                                        overlays.showEquipScanner = false
                                        overlays.scanStatus = null
                                        overlays.scanResult = com.example.wcc_companion_app.data.remote.models.ScanLookupDataDto(
                                            code = code,
                                            kind = "equipment",
                                            hits = listOf(
                                                com.example.wcc_companion_app.data.remote.models.ScanLookupHitDto(
                                                    kind = "equipment",
                                                    data = mapOf(
                                                        "equip_id" to hit.equip_id,
                                                        "asset_uuid" to hit.asset_uuid,
                                                        "equip_name" to hit.equip_name,
                                                        "category" to hit.category,
                                                        "criticality" to hit.criticality,
                                                        "equipment_type" to hit.equipment_type,
                                                        "oem_brand" to hit.oem_brand,
                                                        "oem_model" to hit.oem_model,
                                                        "oem_serial" to hit.oem_serial,
                                                        "plant_name" to hit.plant_name,
                                                        "line_name" to hit.line_name,
                                                        "station_name" to hit.station_name
                                                    )
                                                )
                                            ),
                                            count = 1,
                                            open_tickets = tickets.filter {
                                                it.equip_id == hit.equip_id &&
                                                    !it.status.equals("CLOSED", ignoreCase = true)
                                            },
                                            open_work_orders = workOrders.filter {
                                                it.equipment_id == hit.equip_id &&
                                                    !it.status.equals("Completed", ignoreCase = true) &&
                                                    !it.status.equals("Cancelled", ignoreCase = true)
                                            }
                                        )
                                    } else {
                                        overlays.scanStatus = body?.message
                                            ?: "No equipment / part / tooling for \"$code\""
                                    }
                                }
                            } catch (e: Exception) {
                                overlays.scanStatus = "Server unreachable"
                            }
                            overlays.isLookingUp = false
                        }
                    }
                }
            )
        }

        overlays.scanResult?.let { result ->
            com.example.wcc_companion_app.ui.scan.ScanResultModal(
                result = result,
                onDismiss = { overlays.scanResult = null },
                onScanAgain = {
                    overlays.scanResult = null
                    overlays.scanStatus = null
                    overlays.showEquipScanner = true
                },
                onOpenEquipment = {
                    overlays.scanResult = null
                    mmmNav.focusCategory("equipment")
                    overlays.selectedEquipment = it
                },
                onOpenPart = {
                    overlays.scanResult = null
                    mmmNav.focusCategory("inventory")
                    overlays.selectedPart = it
                },
                onOpenTicket = { t ->
                    overlays.scanResult = null
                    openTicketFromScan(t)
                },
                onOpenWorkOrder = { wo ->
                    overlays.scanResult = null
                    openWorkOrderFromScan(wo)
                }
            )
        }

        overlays.filterSheetDomain?.let { domain ->
            val chips = when (domain) {
                com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT -> listOf(
                    com.example.wcc_companion_app.ui.components.RailFilterChip("A", "CRIT A"),
                    com.example.wcc_companion_app.ui.components.RailFilterChip("B", "CRIT B"),
                    com.example.wcc_companion_app.ui.components.RailFilterChip("C", "CRIT C")
                )
                com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY -> listOf(
                    com.example.wcc_companion_app.ui.components.RailFilterChip("healthy", "OK"),
                    com.example.wcc_companion_app.ui.components.RailFilterChip("low", "LOW"),
                    com.example.wcc_companion_app.ui.components.RailFilterChip("reorder", "REORDER"),
                    com.example.wcc_companion_app.ui.components.RailFilterChip("out", "OUT")
                )
                com.example.wcc_companion_app.ui.components.RailFilterDomain.TOOLINGS -> emptyList()
            }
            com.example.wcc_companion_app.ui.components.RailFilterSheet(
                domain = domain,
                query = when (domain) {
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT -> equipQuery
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.TOOLINGS -> toolingQuery
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY -> invQuery
                },
                chipId = when (domain) {
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT -> equipCritChip
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY -> invStockChip
                    else -> null
                },
                chips = chips,
                matchCount = when (domain) {
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT ->
                        filteredEquipment.size
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.TOOLINGS ->
                        if (toolingsTableOnline) filteredToolings.size else 0
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY ->
                        filteredParts.size
                },
                totalCount = when (domain) {
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT -> equipment.size
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.TOOLINGS ->
                        if (toolingsTableOnline) toolings.size else 0
                    com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY -> parts.size
                },
                onQueryChange = { q ->
                    when (domain) {
                        com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT ->
                            equipQuery = q
                        com.example.wcc_companion_app.ui.components.RailFilterDomain.TOOLINGS ->
                            toolingQuery = q
                        com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY ->
                            invQuery = q
                    }
                },
                onChipSelect = { id ->
                    when (domain) {
                        com.example.wcc_companion_app.ui.components.RailFilterDomain.EQUIPMENT ->
                            equipCritChip = id
                        com.example.wcc_companion_app.ui.components.RailFilterDomain.INVENTORY ->
                            invStockChip = id
                        else -> {}
                    }
                },
                onDismiss = { overlays.filterSheetDomain = null },
                onApply = { overlays.filterSheetDomain = null }
            )
        }
    }
}

