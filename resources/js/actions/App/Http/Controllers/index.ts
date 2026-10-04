import Admin from './Admin';
import AlokasiPetugasController from './AlokasiPetugasController';
import AnalisisController from './AnalisisController';
import AnalisisExportController from './AnalisisExportController';
import Auth from './Auth';
import BappController from './BappController';
import BastController from './BastController';
import DasarHukumController from './DasarHukumController';
import DashboardController from './DashboardController';
import DeadlineBypassRequestController from './DeadlineBypassRequestController';
import DipaController from './DipaController';
import KegiatanController from './KegiatanController';
import KegiatanFrameSampelController from './KegiatanFrameSampelController';
import MaintenanceController from './MaintenanceController';
import MonitoringPenggunaanAplikasiController from './MonitoringPenggunaanAplikasiController';
import MonitoringPenilaianMitraController from './MonitoringPenilaianMitraController';
import MonitoringPulsaController from './MonitoringPulsaController';
import PenandatanganController from './PenandatanganController';
import PengajuanPulsaController from './PengajuanPulsaController';
import PetugasController from './PetugasController';
import PetugasReviewController from './PetugasReviewController';
import ResetUserTwoFactorController from './ResetUserTwoFactorController';
import RoleSwitchController from './RoleSwitchController';
import SampleMasterController from './SampleMasterController';
import SbmlController from './SbmlController';
import SbmlReportController from './SbmlReportController';
import SkKpaController from './SkKpaController';
import SpkController from './SpkController';
import TwoFactorPromptController from './TwoFactorPromptController';
import UserRoleController from './UserRoleController';
import ViewAsUserController from './ViewAsUserController';
import YearSwitchController from './YearSwitchController';
const Controllers = {
    Auth: Object.assign(Auth, Auth),
    SpkController: Object.assign(SpkController, SpkController),
    MaintenanceController: Object.assign(
        MaintenanceController,
        MaintenanceController,
    ),
    TwoFactorPromptController: Object.assign(
        TwoFactorPromptController,
        TwoFactorPromptController,
    ),
    DashboardController: Object.assign(
        DashboardController,
        DashboardController,
    ),
    MonitoringPenggunaanAplikasiController: Object.assign(
        MonitoringPenggunaanAplikasiController,
        MonitoringPenggunaanAplikasiController,
    ),
    MonitoringPenilaianMitraController: Object.assign(
        MonitoringPenilaianMitraController,
        MonitoringPenilaianMitraController,
    ),
    RoleSwitchController: Object.assign(
        RoleSwitchController,
        RoleSwitchController,
    ),
    YearSwitchController: Object.assign(
        YearSwitchController,
        YearSwitchController,
    ),
    ViewAsUserController: Object.assign(
        ViewAsUserController,
        ViewAsUserController,
    ),
    DeadlineBypassRequestController: Object.assign(
        DeadlineBypassRequestController,
        DeadlineBypassRequestController,
    ),
    Admin: Object.assign(Admin, Admin),
    PetugasReviewController: Object.assign(
        PetugasReviewController,
        PetugasReviewController,
    ),
    PetugasController: Object.assign(PetugasController, PetugasController),
    UserRoleController: Object.assign(UserRoleController, UserRoleController),
    ResetUserTwoFactorController: Object.assign(
        ResetUserTwoFactorController,
        ResetUserTwoFactorController,
    ),
    KegiatanController: Object.assign(KegiatanController, KegiatanController),
    AlokasiPetugasController: Object.assign(
        AlokasiPetugasController,
        AlokasiPetugasController,
    ),
    SbmlController: Object.assign(SbmlController, SbmlController),
    PenandatanganController: Object.assign(
        PenandatanganController,
        PenandatanganController,
    ),
    DipaController: Object.assign(DipaController, DipaController),
    DasarHukumController: Object.assign(
        DasarHukumController,
        DasarHukumController,
    ),
    SampleMasterController: Object.assign(
        SampleMasterController,
        SampleMasterController,
    ),
    KegiatanFrameSampelController: Object.assign(
        KegiatanFrameSampelController,
        KegiatanFrameSampelController,
    ),
    SbmlReportController: Object.assign(
        SbmlReportController,
        SbmlReportController,
    ),
    AnalisisController: Object.assign(AnalisisController, AnalisisController),
    AnalisisExportController: Object.assign(
        AnalisisExportController,
        AnalisisExportController,
    ),
    SkKpaController: Object.assign(SkKpaController, SkKpaController),
    BastController: Object.assign(BastController, BastController),
    BappController: Object.assign(BappController, BappController),
    MonitoringPulsaController: Object.assign(
        MonitoringPulsaController,
        MonitoringPulsaController,
    ),
    PengajuanPulsaController: Object.assign(
        PengajuanPulsaController,
        PengajuanPulsaController,
    ),
};

export default Controllers;
