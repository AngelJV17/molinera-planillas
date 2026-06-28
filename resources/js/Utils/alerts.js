import Swal from "sweetalert2";

/**
 * Obtiene una variable CSS del tema.
 */
const cssVariable = (name, fallback) => {
    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();

    return value || fallback;
};

/**
 * Colores base del sistema.
 */
const themeColors = () => {
    return {
        primary: cssVariable("--color-primary", "#2563eb"),
        primaryDark: cssVariable("--color-primary-dark", "#1d4ed8"),
        success: cssVariable("--color-success", "#16a34a"),
        danger: cssVariable("--color-danger", "#dc2626"),
        warning: cssVariable("--color-warning", "#f59e0b"),
        slate: cssVariable("--color-slate", "#64748b"),
    };
};

/**
 * Clases visuales base para SweetAlert2.
 *
 * Importante:
 * - actions controla la separación entre botones.
 * - confirmButton y cancelButton no deben llevar mr/ml.
 * - reverseButtons ya se encarga del orden visual.
 */
const customClasses = {
    popup: "rounded-3xl border border-slate-200 px-2 pb-6 pt-4 shadow-2xl",
    icon: "mt-4",
    title: "px-6 text-xl font-black text-gray-900",
    htmlContainer: "px-6 text-sm font-semibold leading-relaxed text-gray-600",
    actions:
        "mt-6 flex w-full flex-col-reverse gap-3 px-6 sm:flex-row sm:justify-center",
    confirmButton:
        "inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:w-auto",
    cancelButton:
        "inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2 sm:w-auto",
};

/**
 * Configuración base para modales.
 */
const baseModalConfig = {
    buttonsStyling: false,
    reverseButtons: true,
    allowOutsideClick: false,
    allowEscapeKey: true,
    customClass: customClasses,
};

/**
 * Configuración base para toasts.
 */
const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    customClass: {
        popup: "rounded-2xl border border-slate-200 px-4 py-3 shadow-xl",
        title: "text-sm font-bold text-gray-800",
        timerProgressBar: "bg-primary",
    },
});

/**
 * Toast de éxito.
 */
export const showSuccessAlert = (
    message = "Operación realizada correctamente.",
) => {
    return Toast.fire({
        icon: "success",
        title: message,
    });
};

/**
 * Toast de error.
 */
export const showErrorAlert = (
    message = "Ocurrió un error. Inténtalo nuevamente.",
) => {
    return Toast.fire({
        icon: "error",
        title: message,
    });
};

/**
 * Toast de advertencia.
 */
export const showWarningAlert = (
    message = "Revisa la información ingresada.",
) => {
    return Toast.fire({
        icon: "warning",
        title: message,
    });
};

/**
 * Toast informativo.
 */
export const showInfoAlert = (message = "Información del sistema.") => {
    return Toast.fire({
        icon: "info",
        title: message,
    });
};

/**
 * Modal de éxito.
 */
export const showSuccessModal = ({
    title = "Operación exitosa",
    text = "La acción se realizó correctamente.",
    confirmButtonText = "Entendido",
} = {}) => {
    return Swal.fire({
        ...baseModalConfig,
        icon: "success",
        iconColor: themeColors().success,
        title,
        text,
        confirmButtonText,
    });
};

/**
 * Modal de error.
 */
export const showErrorModal = ({
    title = "Ocurrió un error",
    text = "No se pudo completar la acción.",
    confirmButtonText = "Entendido",
} = {}) => {
    return Swal.fire({
        ...baseModalConfig,
        icon: "error",
        iconColor: themeColors().danger,
        title,
        text,
        confirmButtonText,
    });
};

/**
 * Confirmación genérica reutilizable.
 */
export const confirmAction = async ({
    title = "¿Deseas continuar?",
    text = "Esta acción modificará la información registrada.",
    icon = "warning",
    confirmButtonText = "Sí, continuar",
    cancelButtonText = "Cancelar",
    confirmButtonClass = customClasses.confirmButton,
    iconColor = null,
} = {}) => {
    const colors = themeColors();

    const result = await Swal.fire({
        ...baseModalConfig,
        title,
        text,
        icon,
        iconColor: iconColor ?? colors.warning,
        confirmButtonText,
        cancelButtonText,
        showCancelButton: true,
        customClass: {
            ...customClasses,
            confirmButton: confirmButtonClass,
        },
    });

    return result.isConfirmed;
};

/**
 * Confirmación para cerrar asistencia mensual.
 */
export const confirmCloseAttendance = async ({
    employeeName = "este trabajador",
    period = "este periodo",
} = {}) => {
    return confirmAction({
        title: "¿Cerrar asistencia mensual?",
        text: `Se cerrará la asistencia de ${employeeName} correspondiente a ${period}. Luego ya no podrá editarse.`,
        icon: "warning",
        confirmButtonText: "Sí, cerrar asistencia",
        cancelButtonText: "Cancelar",
        iconColor: themeColors().warning,
        confirmButtonClass:
            "inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:w-auto",
    });
};

/**
 * Confirmación para eliminar.
 */
export const confirmDelete = async ({
    title = "¿Eliminar registro?",
    text = "Esta acción no se puede deshacer.",
} = {}) => {
    return confirmAction({
        title,
        text,
        icon: "warning",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        iconColor: themeColors().danger,
        confirmButtonClass:
            "inline-flex w-full items-center justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto",
    });
};

/**
 * Confirmación para cambio de estado.
 */
export const confirmStatusChange = async ({
    title = "¿Cambiar estado?",
    text = "El registro será actualizado.",
} = {}) => {
    return confirmAction({
        title,
        text,
        icon: "question",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar",
        iconColor: themeColors().primary,
        confirmButtonClass:
            "inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:w-auto",
    });
};
