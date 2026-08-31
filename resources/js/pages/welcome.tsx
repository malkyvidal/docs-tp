import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    CheckCircle2,
    Clock,
    FileSignature,
    FileText,
    History,
    Send,
    ShieldCheck,
    UserCheck,
    Users,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, home, login } from '@/routes';

const features = [
    {
        icon: FileText,
        title: 'Gestión centralizada',
        description:
            'Sube, organiza y consulta todos tus documentos en un único repositorio seguro y accesible desde cualquier dispositivo.',
    },
    {
        icon: FileSignature,
        title: 'Firma electrónica',
        description:
            'Firma documentos desde cualquier lugar con la confianza y validez que requiere cada tipo de trámite.',
    },
    {
        icon: Send,
        title: 'Envío para firma',
        description:
            'Invita a los firmantes con un solo clic y deja que el documento llegue a quien debe firmarlo, sin correos ni papel.',
    },
    {
        icon: Users,
        title: 'Roles firmante y administrador',
        description:
            'Separa responsabilidades: los administradores gestionan los flujos y los firmantes se centran en revisar y firmar.',
    },
    {
        icon: History,
        title: 'Auditoría y trazabilidad',
        description:
            'Registro completo de cada acción: quién firmó, cuándo y desde dónde, para mantener total transparencia.',
    },
    {
        icon: ShieldCheck,
        title: 'Seguridad avanzada',
        description:
            'Cifrado de extremo a extremo y control de accesos para que tu información y tus firmas estén siempre protegidas.',
    },
];

const steps = [
    {
        number: '01',
        title: 'Sube y organiza el documento',
        description:
            'Añade el documento a tu espacio de trabajo y prepara el flujo de firma con los firmantes que correspondan.',
    },
    {
        number: '02',
        title: 'Envía para firma',
        description:
            'El documento llega a cada firmante con las instrucciones claras y un seguimiento del estado en tiempo real.',
    },
    {
        number: '03',
        title: 'Firma y archiva',
        description:
            'Cada firmante revisa y firma digitalmente. El documento queda sellado y archivado con su registro de auditoría.',
    },
];

const securityItems = [
    'Cifrado de extremo a extremo de la información',
    'Firma con trazabilidad y registro de auditoría',
    'Copias de seguridad y disponibilidad garantizada',
    'Control de accesos basado en roles (firmante y administrador)',
];

function Header() {
    const { auth } = usePage().props;

    return (
        <header className="sticky top-0 z-50 border-b border-border/60 bg-background/80 backdrop-blur">
            <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href={home()} className="flex items-center gap-2.5">
                    <AppLogoIcon className="size-7 fill-current" />
                    <span className="text-base font-semibold tracking-tight">
                        SignaDocs
                    </span>
                </Link>

                <nav className="hidden items-center gap-8 text-sm font-medium text-muted-foreground md:flex">
                    <a
                        href="#caracteristicas"
                        className="transition-colors hover:text-foreground"
                    >
                        Características
                    </a>
                    <a
                        href="#como-funciona"
                        className="transition-colors hover:text-foreground"
                    >
                        Cómo funciona
                    </a>
                    <a
                        href="#roles"
                        className="transition-colors hover:text-foreground"
                    >
                        Roles
                    </a>
                    <a
                        href="#seguridad"
                        className="transition-colors hover:text-foreground"
                    >
                        Seguridad
                    </a>
                </nav>

                <div className="flex items-center gap-2">
                    {auth.user ? (
                        <Button asChild>
                            <Link href={dashboard()}>Ir al panel</Link>
                        </Button>
                    ) : (
                        <Button asChild>
                            <Link href={login()}>Iniciar sesión</Link>
                        </Button>
                    )}
                </div>
            </div>
        </header>
    );
}

function Hero() {
    return (
        <section className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-3xl py-20 text-center sm:py-28">
                <Badge
                    variant="outline"
                    className="mb-6 rounded-full px-3 py-1"
                >
                    Gestión y firma de documentos
                </Badge>

                <h1 className="text-4xl font-bold tracking-tight sm:text-6xl">
                    Firma tus documentos de forma{' '}
                    <span className="text-primary">segura</span> y sin fricción
                </h1>

                <p className="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                    SignaDocs centraliza la gestión de tus documentos, te
                    permite enviarlos para firma y archivarlos con validez
                    legal, todo desde un único panel profesional.
                </p>

                <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Button size="lg" asChild>
                        <Link href={login()}>
                            Iniciar sesión
                            <ArrowRight />
                        </Link>
                    </Button>
                    <Button size="lg" variant="outline" asChild>
                        <a href="#caracteristicas">Ver características</a>
                    </Button>
                </div>

                <div className="mt-12 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm text-muted-foreground">
                    <span className="inline-flex items-center gap-2">
                        <CheckCircle2 className="size-4 text-primary" />
                        Firma con validez legal
                    </span>
                    <span className="inline-flex items-center gap-2">
                        <ShieldCheck className="size-4 text-primary" />
                        Cifrado de extremo a extremo
                    </span>
                    <span className="inline-flex items-center gap-2">
                        <Clock className="size-4 text-primary" />
                        Seguimiento en tiempo real
                    </span>
                </div>
            </div>
        </section>
    );
}

function Stats() {
    const stats = [
        { value: '+10.000', label: 'Documentos firmados' },
        { value: '99,9%', label: 'Disponibilidad del servicio' },
        { value: '24/7', label: 'Trazabilidad de cada firma' },
    ];

    return (
        <section className="border-y border-border/60 bg-muted/40">
            <div className="mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 py-12 sm:grid-cols-3 sm:px-6 lg:px-8">
                {stats.map((stat) => (
                    <div key={stat.label} className="text-center">
                        <p className="text-3xl font-bold tracking-tight">
                            {stat.value}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {stat.label}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function Features() {
    return (
        <section
            id="caracteristicas"
            className="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 sm:py-24 lg:px-8"
        >
            <div className="mx-auto max-w-2xl text-center">
                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                    Todo lo que necesitas para firmar con confianza
                </h2>
                <p className="mt-4 text-muted-foreground">
                    Una plataforma pensada para que la gestión y la firma de
                    documentos sean rápidas, seguras y verificables.
                </p>
            </div>

            <div className="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {features.map((feature) => (
                    <div
                        key={feature.title}
                        className="rounded-xl border border-border/60 bg-card p-6 transition-colors hover:border-border"
                    >
                        <div className="mb-4 inline-flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <feature.icon className="size-5" />
                        </div>
                        <h3 className="text-base font-semibold">
                            {feature.title}
                        </h3>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {feature.description}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function HowItWorks() {
    return (
        <section
            id="como-funciona"
            className="scroll-mt-20 border-y border-border/60 bg-muted/40"
        >
            <div className="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
                <div className="mx-auto max-w-2xl text-center">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Cómo funciona
                    </h2>
                    <p className="mt-4 text-muted-foreground">
                        De la subida del documento a la firma final en tres
                        pasos sencillos.
                    </p>
                </div>

                <div className="mt-14 grid grid-cols-1 gap-8 md:grid-cols-3">
                    {steps.map((step) => (
                        <div
                            key={step.number}
                            className="relative rounded-xl border border-border/60 bg-background p-6"
                        >
                            <span className="text-sm font-bold text-primary">
                                {step.number}
                            </span>
                            <h3 className="mt-3 text-base font-semibold">
                                {step.title}
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                {step.description}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function Roles() {
    const roles = [
        {
            icon: UserCheck,
            title: 'Firmante',
            description:
                'Recibe los documentos que necesita firmar, los revisa cómodamente y los firma con total seguridad desde cualquier dispositivo.',
        },
        {
            icon: ShieldCheck,
            title: 'Administrador',
            description:
                'Gestiona los documentos y los flujos de firma, asigna firmantes y supervisa el estado y la trazabilidad de cada trámite.',
        },
    ];

    return (
        <section
            id="roles"
            className="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 sm:py-24 lg:px-8"
        >
            <div className="mx-auto max-w-2xl text-center">
                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                    Dos perfiles, un mismo flujo
                </h2>
                <p className="mt-4 text-muted-foreground">
                    La plataforma se adapta al rol de cada usuario para que cada
                    uno haga exactamente lo que le corresponde.
                </p>
            </div>

            <div className="mt-14 grid grid-cols-1 gap-6 md:grid-cols-2">
                {roles.map((role) => (
                    <div
                        key={role.title}
                        className="flex flex-col rounded-xl border border-border/60 bg-card p-8 transition-colors hover:border-border"
                    >
                        <div className="mb-4 inline-flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <role.icon className="size-6" />
                        </div>
                        <h3 className="text-lg font-semibold">{role.title}</h3>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {role.description}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function Security() {
    return (
        <section
            id="seguridad"
            className="scroll-mt-20 border-y border-border/60 bg-muted/40"
        >
            <div className="mx-auto grid max-w-6xl grid-cols-1 items-center gap-12 px-4 py-20 sm:px-6 sm:py-24 lg:grid-cols-2 lg:px-8">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Tu documentación, protegida en todo momento
                    </h2>
                    <p className="mt-4 text-muted-foreground">
                        Diseñamos SignaDocs con la seguridad como prioridad,
                        para que firmar sea tan fiable como archivar.
                    </p>
                </div>

                <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {securityItems.map((item) => (
                        <li
                            key={item}
                            className="flex items-start gap-3 rounded-xl border border-border/60 bg-background p-4"
                        >
                            <span className="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <Check className="size-3.5" />
                            </span>
                            <span className="text-sm leading-relaxed">
                                {item}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

function FinalCta() {
    return (
        <section className="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
            <div className="relative overflow-hidden rounded-2xl border border-border/60 bg-primary px-6 py-16 text-center text-primary-foreground sm:px-12">
                <div className="relative z-10 mx-auto max-w-2xl">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Empieza a gestionar y firmar hoy mismo
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-primary-foreground/80">
                        Accede a tu cuenta y descubre un flujo de firma
                        profesional, seguro y sin fricción.
                    </p>
                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Button
                            size="lg"
                            variant="secondary"
                            className="bg-background text-foreground hover:bg-background/90"
                            asChild
                        >
                            <Link href={login()}>
                                Iniciar sesión
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}

function Footer() {
    const { auth } = usePage().props;

    return (
        <footer className="border-t border-border/60">
            <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6 lg:px-8">
                <div className="flex items-center gap-2.5">
                    <AppLogoIcon className="size-6 fill-current" />
                    <span className="text-sm font-semibold tracking-tight">
                        SignaDocs
                    </span>
                </div>

                <div className="flex items-center gap-6 text-sm text-muted-foreground">
                    {!auth.user && (
                        <Link
                            href={login()}
                            className="transition-colors hover:text-foreground"
                        >
                            Iniciar sesión
                        </Link>
                    )}
                </div>

                <p className="text-sm text-muted-foreground">
                    &copy; {new Date().getFullYear()} SignaDocs. Todos los
                    derechos reservados.
                </p>
            </div>
        </footer>
    );
}

export default function Welcome() {
    return (
        <>
            <Head title="Bienvenido" />
            <div className="flex min-h-svh flex-col bg-background text-foreground">
                <Header />
                <main className="flex-1">
                    <Hero />
                    <Stats />
                    <Features />
                    <HowItWorks />
                    <Roles />
                    <Security />
                    <FinalCta />
                </main>
                <Footer />
            </div>
        </>
    );
}
