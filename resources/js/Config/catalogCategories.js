import {
    BriefcaseBusiness,
    Building2,
    CalendarCheck,
    CalendarDays,
    CreditCard,
    HeartHandshake,
    IdCard,
    Landmark,
    ListChecks,
    RefreshCcw,
    UserCheck,
    VenusAndMars,
} from "lucide-vue-next";

/**
 * Categorías disponibles para el módulo de catálogos.
 *
 * Este archivo centraliza la información visual y descriptiva
 * para evitar repetir la misma configuración en Index, Create y Edit.
 */
export const catalogCategories = [
    {
        key: "DOCUMENT_TYPE",
        label: "Tipos de documento",
        singular: "Tipo de documento",
        description:
            "Opciones usadas para identificar al trabajador, como DNI, Carné de Extranjería o Pasaporte.",
        examples: "DNI, Carné de Extranjería, Pasaporte",
        icon: IdCard,
    },
    {
        key: "GENDER",
        label: "Géneros",
        singular: "Género",
        description: "Opciones usadas para registrar el género del trabajador.",
        examples: "Masculino, Femenino",
        icon: VenusAndMars,
    },
    {
        key: "MARITAL_STATUS",
        label: "Estados civiles",
        singular: "Estado civil",
        description:
            "Opciones usadas para registrar la situación civil del trabajador.",
        examples: "Soltero(a), Casado(a), Conviviente, Viudo(a)",
        icon: HeartHandshake,
    },
    {
        key: "WORK_AREA",
        label: "Áreas de trabajo",
        singular: "Área de trabajo",
        description:
            "Áreas internas de la empresa donde puede laborar un trabajador.",
        examples: "Administración, Producción, Almacén, Seguridad",
        icon: Building2,
    },
    {
        key: "PAYROLL_GROUP",
        label: "Grupos de planilla",
        singular: "Grupo de planilla",
        description:
            "Agrupaciones usadas para generar planillas sin reemplazar el area real del trabajador.",
        examples: "Planilla administrativa, Planilla personal de produccion",
        icon: ListChecks,
    },
    {
        key: "POSITION",
        label: "Cargos",
        singular: "Cargo",
        description:
            "Puestos o cargos que pueden asignarse a los trabajadores.",
        examples: "Administrador, Contador, Operario de Molino, Vigilante",
        icon: BriefcaseBusiness,
    },
    {
        key: "WORKER_STATUS",
        label: "Estados laborales",
        singular: "Estado laboral",
        description:
            "Estados usados para controlar la situación laboral del trabajador.",
        examples: "Activo, Vacaciones, Licencia, Suspendido, Cesado",
        icon: UserCheck,
    },
    {
        key: "PENSION_SYSTEM",
        label: "Sistemas pensionarios",
        singular: "Sistema pensionario",
        description:
            "Opciones usadas para registrar el régimen pensionario del trabajador.",
        examples: "ONP, AFP Integra, AFP Prima, Ninguno",
        icon: Landmark,
    },
    {
        key: "ACCOUNT_TYPE",
        label: "Tipos de cuenta",
        singular: "Tipo de cuenta",
        description:
            "Opciones usadas para registrar el tipo de cuenta bancaria del trabajador.",
        examples: "Cuenta de Ahorros, Cuenta Corriente",
        icon: CreditCard,
    },
    {
        key: "ATTENDANCE_MONTHLY_STATUS",
        label: "Estados de asistencia mensual",
        singular: "Estado mensual",
        description:
            "Estados usados para controlar la cabecera mensual de asistencia de cada trabajador.",
        examples: "Borrador, Cerrada",
        icon: CalendarDays,
    },
    {
        key: "ATTENDANCE_DAY_STATUS",
        label: "Estados del calendario",
        singular: "Estado diario",
        description:
            "Estados usados para marcar cada día dentro del calendario mensual de asistencia.",
        examples: "Sin marcar, Asistió, Faltó, Descanso, Trabajó como canje",
        icon: CalendarCheck,
    },
    {
        key: "ATTENDANCE_EXCHANGE_STATUS",
        label: "Estados de canje",
        singular: "Estado de canje",
        description:
            "Estados usados para controlar la relación entre una falta y el día trabajado para compensarla.",
        examples: "Pendiente, Aplicado, Anulado",
        icon: RefreshCcw,
    },
];

/**
 * Categoría por defecto cuando el tipo recibido no existe
 * dentro de la configuración registrada.
 */
export const fallbackCatalogCategory = {
    key: "UNKNOWN",
    label: "Configuraciones",
    singular: "Configuración",
    description: "Opción reutilizable del sistema.",
    examples: "Sin ejemplos registrados",
    icon: ListChecks,
};

/**
 * Busca una categoría por su código interno.
 */
export const getCatalogCategory = (type) => {
    return (
        catalogCategories.find((category) => category.key === type) ??
        fallbackCatalogCategory
    );
};
