package com.example.wcc_companion_app.data.local

import androidx.room.Database
import androidx.room.RoomDatabase
import com.example.wcc_companion_app.data.local.dao.EquipmentDao
import com.example.wcc_companion_app.data.local.dao.PartDao
import com.example.wcc_companion_app.data.local.dao.PendingMediaDao
import com.example.wcc_companion_app.data.local.dao.PendingOpDao
import com.example.wcc_companion_app.data.local.dao.TicketDao
import com.example.wcc_companion_app.data.local.dao.ToolingDao
import com.example.wcc_companion_app.data.local.dao.WorkOrderDao
import com.example.wcc_companion_app.data.local.entity.LocalEquipmentEntity
import com.example.wcc_companion_app.data.local.entity.LocalPartEntity
import com.example.wcc_companion_app.data.local.entity.LocalTicketEntity
import com.example.wcc_companion_app.data.local.entity.LocalToolingEntity
import com.example.wcc_companion_app.data.local.entity.LocalWorkOrderEntity
import com.example.wcc_companion_app.data.local.entity.PendingMediaEntity
import com.example.wcc_companion_app.data.local.entity.PendingTicketOpEntity

@Database(
    entities = [
        LocalTicketEntity::class,
        PendingTicketOpEntity::class,
        LocalEquipmentEntity::class,
        LocalPartEntity::class,
        LocalToolingEntity::class,
        LocalWorkOrderEntity::class,
        PendingMediaEntity::class,
    ],
    version = 3,
    exportSchema = false
)
abstract class WccDatabase : RoomDatabase() {
    abstract fun ticketDao(): TicketDao
    abstract fun pendingOpDao(): PendingOpDao
    abstract fun equipmentDao(): EquipmentDao
    abstract fun partDao(): PartDao
    abstract fun toolingDao(): ToolingDao
    abstract fun workOrderDao(): WorkOrderDao
    abstract fun pendingMediaDao(): PendingMediaDao
}
