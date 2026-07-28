package com.example.wcc_companion_app.di

import android.content.Context
import androidx.room.Room
import com.example.wcc_companion_app.data.local.WccDatabase
import com.example.wcc_companion_app.data.local.dao.EquipmentDao
import com.example.wcc_companion_app.data.local.dao.PartDao
import com.example.wcc_companion_app.data.local.dao.PendingMediaDao
import com.example.wcc_companion_app.data.local.dao.PendingOpDao
import com.example.wcc_companion_app.data.local.dao.TicketDao
import com.example.wcc_companion_app.data.local.dao.ToolingDao
import com.example.wcc_companion_app.data.local.dao.WorkOrderDao
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {

    @Provides
    @Singleton
    fun provideDatabase(@ApplicationContext context: Context): WccDatabase {
        return Room.databaseBuilder(
            context,
            WccDatabase::class.java,
            "wcc_companion.db"
        ).fallbackToDestructiveMigration()
            .build()
    }

    @Provides fun ticketDao(db: WccDatabase): TicketDao = db.ticketDao()
    @Provides fun pendingOpDao(db: WccDatabase): PendingOpDao = db.pendingOpDao()
    @Provides fun equipmentDao(db: WccDatabase): EquipmentDao = db.equipmentDao()
    @Provides fun partDao(db: WccDatabase): PartDao = db.partDao()
    @Provides fun toolingDao(db: WccDatabase): ToolingDao = db.toolingDao()
    @Provides fun workOrderDao(db: WccDatabase): WorkOrderDao = db.workOrderDao()
    @Provides fun pendingMediaDao(db: WccDatabase): PendingMediaDao = db.pendingMediaDao()
}
