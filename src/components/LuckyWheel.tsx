import {
  useEffect,
  useMemo,
  useRef,
  useState,
  type CSSProperties,
} from "react";
import {
  authorize,
  closeApp,
  getPhoneNumber,
  getSetting,
  getUserInfo,
  interactOA,
  openChat,
  openOutApp,
  openProfile,
  showToast,
} from "zmp-sdk";
import "@/css/lucky-wheel.scss";

type Stage = "booting" | "form" | "loading" | "wheel" | "unavailable";

type Theme = {
  updated_at?: string | null;
  primary_color?: string | null;
  secondary_color?: string | null;
  accent_color?: string | null;
  background_style?: string | null;
  background_asset_path?: string | null;
  background_asset_url?: string | null;
  wheel?: {
    palettePreset?: string | null;
    borderPreset?: string | null;
    borderPresetId?: number | null;
    borderAssetPath?: string | null;
    borderAssetUrl?: string | null;
    pointerPreset?: string | null;
    pointerPresetId?: number | null;
    pointerAssetPath?: string | null;
    pointerAssetUrl?: string | null;
    centerLabel?: string | null;
    previewNote?: string | null;
  } | null;
  assets?: Record<
    string,
    {
      slotType?: string | null;
      presetId?: number | null;
      overridePath?: string | null;
      assetPath?: string | null;
      assetUrl?: string | null;
      label?: string | null;
      source?: string | null;
    }
  > | null;
  theme_tokens?: {
    button_color?: string | null;
    text_color?: string | null;
    background_color?: string | null;
  } | null;
};

type FormField = {
  fieldKey: string;
  type: "text" | "select" | "email" | "tel" | string;
  label: string;
  placeholder: string | null;
  helpText?: string | null;
  isRequired: boolean;
  options?: string[] | null;
};

type Prize = {
  code: string;
  label: string;
  description: string | null;
  imageAssetPath?: string | null;
  valueLabel?: string | null;
  tone: string;
  shortLabel: string;
};

type WonPrizeHistoryItem = {
  spinResultId: number;
  label: string;
  description: string | null;
};

type BootstrapResponse = {
  available: boolean;
  message?: string;
  game: {
    name: string;
    slug: string;
    templateType: string;
    status: string;
    publicIdentifier: string;
  };
  theme?: Theme | null;
  content?: Record<string, string>;
  formFields: FormField[];
  prizes: Array<{
    code: string;
    label: string;
    description: string | null;
    imageAssetPath?: string | null;
    valueLabel?: string | null;
  }>;
  rules?: {
    requiresRewardCode?: boolean;
    maxSpinsPerPlayer?: number;
    redirectStrategy?: string | null;
  };
  redirect?: {
    action?: string | null;
    targetType?: string | null;
    targetValue?: string | null;
    fallbackValue?: string | null;
    messageTemplate?: string | null;
  } | null;
};

type SubmissionResponse = {
  playerPublicId: string;
  submissionId: number;
};

type ZaloProfile = {
  id: string;
  idByOA?: string;
  name?: string;
  avatar?: string;
  followedOA?: boolean;
};

type EligibilityResponse = {
  eligible: boolean;
  playerPublicId?: string;
  remainingSpins?: number;
  reason?: string;
};

type SpinResponse = {
  spinResultId: number;
  resultType: string;
  claimStatus: string;
  prize: {
    code: string;
    label: string;
    description: string | null;
  } | null;
};

type ClaimResponse = {
  claimId: number;
  status: string;
  action?: string | null;
  redirectTarget?: string | null;
  metadata?: {
    fallback_value?: string | null;
    target_type?: string | null;
    message_template?: string | null;
  };
};

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ?? "http://127.0.0.1:8000/api";
const API_ORIGIN = (() => {
  try {
    return new URL(API_BASE_URL).origin;
  } catch {
    return window.location.origin;
  }
})();

function normalizeRuntimeAssetUrl(value: string | null | undefined) {
  const nextValue = value?.trim();

  if (!nextValue) {
    return null;
  }

  if (nextValue.startsWith("data:")) {
    return nextValue;
  }

  try {
    if (/^https?:\/\//i.test(nextValue)) {
      const parsed = new URL(nextValue);

      return `${API_ORIGIN}${parsed.pathname}${parsed.search}${parsed.hash}`;
    }

    if (nextValue.startsWith("/")) {
      return `${API_ORIGIN}${nextValue}`;
    }

    return `${API_ORIGIN}/${nextValue.replace(/^\/+/, "")}`;
  } catch {
    return nextValue;
  }
}

function appendCacheKey(
  value: string | null | undefined,
  cacheKey: string | null | undefined,
) {
  const nextValue = value?.trim();
  const nextCacheKey = cacheKey?.trim();

  if (!nextValue || !nextCacheKey) {
    return nextValue ?? null;
  }

  try {
    const parsed = new URL(nextValue, window.location.origin);
    parsed.searchParams.set("v", nextCacheKey);
    return parsed.toString();
  } catch {
    const separator = nextValue.includes("?") ? "&" : "?";
    return `${nextValue}${separator}v=${encodeURIComponent(nextCacheKey)}`;
  }
}

function logAssetLoadError(label: string, url: string | null) {
  console.error(`[LuckyWheel] Asset load failed: ${label}`, {
    url,
    apiBaseUrl: API_BASE_URL,
    apiOrigin: API_ORIGIN,
  });
}

function hideBrokenImage(
  event: React.SyntheticEvent<HTMLImageElement>,
  label: string,
  url: string | null,
) {
  logAssetLoadError(label, url);
  event.currentTarget.style.display = "none";
}
const FALLBACK_GAME_IDENTIFIER =
  import.meta.env.VITE_GAME_IDENTIFIER ?? "ohar-yeu-thuong";
const PRIZE_TONES = [
  "#fff1c8",
  "#f9c667",
  "#fff6e3",
  "#f1b243",
  "#ffedbb",
  "#f7d888",
];
const PALETTE_PRESETS: Record<string, string[]> = {
  sunrise: ["#fdf1d0", "#ffcf64", "#ff914d", "#ff5040"],
  marine: ["#edf5ff", "#1e63a4", "#114d86", "#0c355e"],
  "soft-pop": ["#ffd8bf", "#f7d9cd", "#a8a0f1", "#7b58e5"],
  mint: ["#f5d0b5", "#f5f1ee", "#abd8d1", "#76a8a9"],
  candy: ["#dbdbdb", "#f6d1c5", "#f2a7b8", "#f98d9b"],
  neon: ["#20b9ad", "#b2dee7", "#f7f8fc", "#ffb869"],
};
const REWARD_CODE_HINTS = ["reward_code", "ma_du_thuong", "voucher_code"];
const NAME_FIELD_HINTS = ["full_name", "ho_va_ten", "ho_ten", "ten", "name"];
const PHONE_FIELD_HINTS = [
  "phone",
  "so_dien_thoai",
  "sdt",
  "dien_thoai",
  "mobile",
];

function buildInitialForm(formFields: FormField[]) {
  return formFields.reduce<Record<string, string>>((accumulator, field) => {
    accumulator[field.fieldKey] = "";
    return accumulator;
  }, {});
}

function getGameIdentifier() {
  const params = new URLSearchParams(window.location.search);
  const pathSegments = window.location.pathname
    .split("/")
    .map((segment) => segment.trim())
    .filter(Boolean);
  const pathIdentifier =
    pathSegments[0] === "play" && pathSegments[1]
      ? decodeURIComponent(pathSegments[1])
      : null;

  return (
    pathIdentifier ??
    params.get("game") ??
    params.get("slug") ??
    params.get("public_id") ??
    FALLBACK_GAME_IDENTIFIER
  );
}

function getPrizeAngle(index: number, prizeCount: number) {
  return (360 / Math.max(prizeCount, 1)) * index;
}

function getPrizeContentStyle(
  _index: number,
  prizeCount: number,
): CSSProperties {
  const segmentAngle = 360 / Math.max(prizeCount, 1);

  return {
    transform: `translate(-50%, -50%) rotate(${segmentAngle / 2}deg) translateY(-108px)`,
    width: prizeCount <= 4 ? "34%" : "28%",
  };
}

function buildWheelBackground(prizes: Prize[]) {
  if (prizes.length === 0) {
    return "conic-gradient(#fff7dc 0deg 360deg)";
  }

  const segmentAngle = 360 / prizes.length;
  const segments = prizes
    .map((prize, index) => {
      const start = index * segmentAngle;
      const end = start + segmentAngle;
      return `${prize.tone} ${start}deg ${end}deg`;
    })
    .join(", ");

  return `conic-gradient(${segments})`;
}

function getPaletteTones(palettePreset?: string | null) {
  const palette = palettePreset ? PALETTE_PRESETS[palettePreset] : null;

  return palette && palette.length > 0 ? palette : PRIZE_TONES;
}

function toShortLabel(label: string) {
  return label.replace(/\s+/g, " ").trim().split(" ").slice(0, 3).join(" ");
}

function normalisePrizeList(
  prizes: BootstrapResponse["prizes"],
  palettePreset?: string | null,
): Prize[] {
  const tones = getPaletteTones(palettePreset);

  return prizes.map((prize, index) => ({
    ...prize,
    tone: tones[index % tones.length],
    shortLabel: toShortLabel(prize.valueLabel ?? prize.label),
  }));
}

function buildResultPrize(
  response: SpinResponse,
  matchedPrize: Prize | null,
  fallbackTone: string,
): Prize {
  if (matchedPrize) {
    return matchedPrize;
  }

  const label =
    response.prize?.label ??
    (response.resultType === "no_prize"
      ? "Chúc bạn may mắn lần sau"
      : "Kết quả đang được cập nhật");
  const description =
    response.prize?.description ??
    (response.resultType === "no_prize"
      ? "Bạn chưa nhận được phần thưởng ở lượt quay này."
      : "Hệ thống đã ghi nhận kết quả quay của bạn.");

  return {
    code: response.prize?.code ?? `result-${response.spinResultId}`,
    label,
    description,
    tone: fallbackTone,
    shortLabel: toShortLabel(label),
  };
}

function getRequiredFieldErrors(
  formFields: FormField[],
  formData: Record<string, string>,
) {
  return formFields.reduce<Record<string, string>>((accumulator, field) => {
    const value = formData[field.fieldKey]?.trim() ?? "";

    if (field.isRequired && value.length === 0) {
      accumulator[field.fieldKey] = "Vui lòng nhập thông tin này";
      return accumulator;
    }

    if (
      field.type === "select" &&
      value.length > 0 &&
      Array.isArray(field.options) &&
      !field.options.includes(value)
    ) {
      accumulator[field.fieldKey] = "Giá trị không hợp lệ";
      return accumulator;
    }

    if (
      field.type === "email" &&
      value.length > 0 &&
      !/\S+@\S+\.\S+/.test(value)
    ) {
      accumulator[field.fieldKey] = "Email không hợp lệ";
      return accumulator;
    }

    if (
      field.type === "tel" &&
      value.length > 0 &&
      !/^[0-9+\s-]{8,15}$/.test(value)
    ) {
      accumulator[field.fieldKey] = "Số điện thoại không hợp lệ";
    }

    return accumulator;
  }, {});
}

function normaliseLookupText(value: string | null | undefined) {
  return (value ?? "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

function resolveRewardCodeValue(
  formFields: FormField[],
  formData: Record<string, string>,
) {
  const directValue = formData.reward_code?.trim();

  if (directValue) {
    return directValue;
  }

  const rewardCodeField = formFields.find((field) => {
    const candidates = [
      field.fieldKey,
      field.label,
      field.placeholder ?? "",
      field.helpText ?? "",
    ].map(normaliseLookupText);

    return REWARD_CODE_HINTS.some((hint) =>
      candidates.some((candidate) => candidate.includes(hint)),
    );
  });

  if (!rewardCodeField) {
    return undefined;
  }

  return formData[rewardCodeField.fieldKey]?.trim() || undefined;
}

function findFieldKeyByHints(
  formFields: FormField[],
  hints: string[],
  preferredType?: "tel" | "email",
) {
  if (preferredType) {
    const byType = formFields.find((field) => field.type === preferredType);
    if (byType) {
      return byType.fieldKey;
    }
  }

  return formFields.find((field) => {
    const candidates = [
      field.fieldKey,
      field.label,
      field.placeholder ?? "",
      field.helpText ?? "",
    ].map(normaliseLookupText);

    return hints.some((hint) =>
      candidates.some((candidate) => candidate.includes(hint)),
    );
  })?.fieldKey;
}

function mergeOnlyEmptyFields(
  current: Record<string, string>,
  nextValues: Record<string, string>,
) {
  const next = { ...current };
  const updatedKeys: string[] = [];

  Object.entries(nextValues).forEach(([fieldKey, value]) => {
    const incomingValue = value.trim();
    const existingValue = current[fieldKey]?.trim() ?? "";

    if (!incomingValue || existingValue) {
      return;
    }

    next[fieldKey] = value;
    updatedKeys.push(fieldKey);
  });

  return {
    next,
    updatedKeys,
  };
}

function buildSpinIdempotencyKey(playerPublicId: string, submissionId: number) {
  const uniqueSuffix =
    typeof crypto !== "undefined" && typeof crypto.randomUUID === "function"
      ? crypto.randomUUID()
      : `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

  return `${playerPublicId}-${submissionId}-${uniqueSuffix}`;
}

async function requestJson<T>(path: string, init?: RequestInit): Promise<T> {
  const headers = new Headers(init?.headers ?? {});
  headers.set("ngrok-skip-browser-warning", "true");

  const method = (init?.method ?? "GET").toUpperCase();
  const hasBody = init?.body !== undefined && init?.body !== null;

  if (hasBody && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    cache: init?.cache ?? "no-store",
    headers,
    method,
  });

  const data = (await response.json().catch(() => ({}))) as T & {
    message?: string;
    errors?: Record<string, string[]>;
  };

  if (!response.ok) {
    throw new Error(
      data.message ??
        Object.values(data.errors ?? {})[0]?.[0] ??
        "Da co loi xay ra",
    );
  }

  return data;
}

async function runClaimAction(claim: ClaimResponse) {
  const redirectTarget =
    claim.redirectTarget ?? claim.metadata?.fallback_value ?? null;
  const targetType = claim.metadata?.target_type ?? null;
  const messageTemplate = claim.metadata?.message_template ?? undefined;

  if (claim.action === "open_oa") {
    const oaTarget = claim.redirectTarget?.trim() ?? "";

    if (oaTarget) {
      if (targetType === "oa_chat" || targetType === "zalo_oa_chat") {
        await openChat({
          type: "oa",
          id: oaTarget,
          ...(messageTemplate ? { message: messageTemplate } : {}),
        });
        return;
      }

      if (targetType === "oa_profile" || targetType === "zalo_oa_profile") {
        await openProfile({
          type: "oa",
          id: oaTarget,
        });
        return;
      }

      if (targetType === "alias_oa" || targetType === "zalo_alias_oa") {
        await openProfile({
          type: "aliasOA",
          id: oaTarget,
        });
        return;
      }

      if (targetType === "oa_interact") {
        await interactOA({
          oaId: oaTarget,
        });
        return;
      }
    }
  }

  if (redirectTarget) {
    if (/^https?:\/\//i.test(redirectTarget)) {
      try {
        await openOutApp({ url: redirectTarget });
        return;
      } catch {
        window.location.href = redirectTarget;
        return;
      }
    }

    window.location.href = redirectTarget;
    return;
  }

  if (claim.action === "close_app") {
    await closeApp();
    return;
  }

  try {
    await closeApp();
  } catch {
    window.history.back();
  }
}

export default function LuckyWheel() {
  const gameIdentifier = useMemo(() => getGameIdentifier(), []);
  const [stage, setStage] = useState<Stage>("booting");
  const [bootstrap, setBootstrap] = useState<BootstrapResponse | null>(null);
  const [prizes, setPrizes] = useState<Prize[]>([]);
  const [formData, setFormData] = useState<Record<string, string>>({});
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [winner, setWinner] = useState<Prize | null>(null);
  const [pendingPrize, setPendingPrize] = useState<Prize | null>(null);
  const [pendingResultType, setPendingResultType] = useState<string | null>(
    null,
  );
  const [rotation, setRotation] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSpinning, setIsSpinning] = useState(false);
  const [isClaiming, setIsClaiming] = useState(false);
  const [loadingProgress, setLoadingProgress] = useState(18);
  const [unavailableMessage, setUnavailableMessage] = useState("");
  const [playerPublicId, setPlayerPublicId] = useState<string | null>(null);
  const [submissionId, setSubmissionId] = useState<number | null>(null);
  const [remainingSpins, setRemainingSpins] = useState<number | null>(null);
  const [spinResultId, setSpinResultId] = useState<number | null>(null);
  const [claimAction, setClaimAction] = useState<string | null>(null);
  const [claimRedirectTarget, setClaimRedirectTarget] = useState<string | null>(
    null,
  );
  const [wonPrizeHistory, setWonPrizeHistory] = useState<WonPrizeHistoryItem[]>(
    [],
  );
  const [zaloProfile, setZaloProfile] = useState<ZaloProfile | null>(null);
  const [zaloPhoneNumber, setZaloPhoneNumber] = useState<string | null>(null);
  const [zaloPhoneToken, setZaloPhoneToken] = useState<string | null>(null);
  const [isLoadingZaloProfile, setIsLoadingZaloProfile] = useState(false);
  const [isLoadingZaloPhone, setIsLoadingZaloPhone] = useState(false);
  const loadingTimer = useRef<number | null>(null);
  const progressTimer = useRef<number | null>(null);

  const formFields = bootstrap?.formFields ?? [];
  const content = bootstrap?.content ?? {};
  const backgroundStyle = bootstrap?.theme?.background_style ?? "warm_gradient";
  const wheelTheme = bootstrap?.theme?.wheel ?? null;
  const themeAssets = bootstrap?.theme?.assets ?? null;
  const themeAssetVersion = bootstrap?.theme?.updated_at ?? null;
  const backgroundAssetUrl = appendCacheKey(
    normalizeRuntimeAssetUrl(
      themeAssets?.background?.assetUrl ??
        bootstrap?.theme?.background_asset_url ??
        null,
    ),
    themeAssetVersion,
  );
  const bannerAssetUrl = appendCacheKey(
    normalizeRuntimeAssetUrl(themeAssets?.banner?.assetUrl ?? null),
    themeAssetVersion,
  );
  const spinButtonAssetUrl = appendCacheKey(
    normalizeRuntimeAssetUrl(themeAssets?.spin_button?.assetUrl ?? null),
    themeAssetVersion,
  );
  const extraSpinButtonAssetUrl = appendCacheKey(
    normalizeRuntimeAssetUrl(themeAssets?.extra_spin_button?.assetUrl ?? null),
    themeAssetVersion,
  );
  const paletteTones = useMemo(
    () => getPaletteTones(wheelTheme?.palettePreset),
    [wheelTheme?.palettePreset],
  );
  const borderPreset = wheelTheme?.borderPreset ?? "classic-red";
  const borderAssetUrl = appendCacheKey(
    normalizeRuntimeAssetUrl(
      themeAssets?.wheel_border?.assetUrl ?? wheelTheme?.borderAssetUrl ?? null,
    ),
    themeAssetVersion,
  );
  const pointerAssetUrl = appendCacheKey(
    normalizeRuntimeAssetUrl(
      themeAssets?.wheel_pointer?.assetUrl ??
        wheelTheme?.pointerAssetUrl ??
        null,
    ),
    themeAssetVersion,
  );
  const centerLabel = wheelTheme?.centerLabel?.trim() || "19T";
  const previewNote = wheelTheme?.previewNote?.trim() || "";
  const shellStyle = useMemo(
    () =>
      ({
        "--brand-primary":
          bootstrap?.theme?.primary_color ??
          bootstrap?.theme?.theme_tokens?.button_color ??
          "#f9c667",
        "--brand-primary-soft": bootstrap?.theme?.secondary_color ?? "#fff3cf",
        "--brand-primary-deep": bootstrap?.theme?.accent_color ?? "#dd9f2f",
        "--brand-text": bootstrap?.theme?.theme_tokens?.text_color ?? "#71490c",
        "--brand-background":
          bootstrap?.theme?.theme_tokens?.background_color ?? "#fff8eb",
        "--wheel-center-text": bootstrap?.theme?.accent_color ?? "#d26757",
        "--brand-background-image": backgroundAssetUrl
          ? `url("${backgroundAssetUrl}")`
          : "none",
      }) as CSSProperties,
    [backgroundAssetUrl, bootstrap],
  );

  useEffect(() => {
    void (async () => {
      try {
        const response = await requestJson<BootstrapResponse>(
          `/games/${gameIdentifier}/bootstrap`,
        );

        if (!response.available) {
          setUnavailableMessage(
            response.message ?? "Trò chơi tạm thời không khả dụng",
          );
          setStage("unavailable");
          return;
        }

        console.info("[LuckyWheel] Bootstrap loaded", {
          apiBaseUrl: API_BASE_URL,
          gameIdentifier,
          backgroundAssetUrl: normalizeRuntimeAssetUrl(
            response.theme?.assets?.background?.assetUrl ??
              response.theme?.background_asset_url ??
              null,
          ),
          bannerAssetUrl: normalizeRuntimeAssetUrl(
            response.theme?.assets?.banner?.assetUrl ?? null,
          ),
          borderAssetUrl: normalizeRuntimeAssetUrl(
            response.theme?.assets?.wheel_border?.assetUrl ??
              response.theme?.wheel?.borderAssetUrl ??
              null,
          ),
          pointerAssetUrl: normalizeRuntimeAssetUrl(
            response.theme?.assets?.wheel_pointer?.assetUrl ??
              response.theme?.wheel?.pointerAssetUrl ??
              null,
          ),
        });

        setBootstrap(response);
        setPrizes(
          normalisePrizeList(
            response.prizes,
            response.theme?.wheel?.palettePreset ?? null,
          ),
        );
        setFormData(buildInitialForm(response.formFields));
        setStage("form");
      } catch (error) {
        console.error("[LuckyWheel] Bootstrap failed", error);
        const message =
          error instanceof Error
            ? error.message
            : "Không tải được dữ liệu trò chơi";
        setUnavailableMessage(message);
        setStage("unavailable");
      }
    })();
  }, [gameIdentifier]);

  useEffect(() => {
    return () => {
      if (loadingTimer.current) {
        window.clearTimeout(loadingTimer.current);
      }
      if (progressTimer.current) {
        window.clearInterval(progressTimer.current);
      }
    };
  }, []);

  const isFormValid =
    Object.keys(getRequiredFieldErrors(formFields, formData)).length === 0;

  const headerTitle =
    content.subtitle ?? bootstrap?.game.name ?? "vòng quay ưu đãi";
  const mainTitle = content.title ?? "yêu Thương";
  const spinButtonLabel = content.spin_button ?? "Quay ngay";
  const continueButtonLabel = content.continue_button ?? "Tiếp tục";
  const loadingLabel = content.loading_message ?? "Đang tải...";
  const hasRemainingSpins = (remainingSpins ?? 0) > 0;

  const applyAutofillValues = async (
    nextValues: Record<string, string>,
    successMessage: string,
    fallbackMessage: string,
  ) => {
    const { next, updatedKeys } = mergeOnlyEmptyFields(formData, nextValues);

    if (updatedKeys.length === 0) {
      await showToast({ message: fallbackMessage });
      return;
    }

    setFormData(next);
    setFieldErrors((current) => {
      const cleaned = { ...current };
      updatedKeys.forEach((fieldKey) => {
        delete cleaned[fieldKey];
      });
      return cleaned;
    });

    await showToast({ message: successMessage });
  };

  const ensureZaloScopes = async (
    scopes: Array<"scope.userInfo" | "scope.userPhonenumber">,
  ) => {
    const settings = await getSetting({});
    const missingScopes = scopes.filter(
      (scope) => !settings.authSetting?.[scope],
    );

    if (missingScopes.length > 0) {
      await authorize({
        scopes: missingScopes,
      });
    }
  };

  const handleConnectZalo = async () => {
    setIsLoadingZaloProfile(true);

    try {
      await ensureZaloScopes(["scope.userInfo"]);
      const { userInfo } = await getUserInfo({});
      const nextProfile: ZaloProfile = {
        id: userInfo.id,
        idByOA: userInfo.idByOA,
        name: userInfo.name,
        avatar: userInfo.avatar,
        followedOA: userInfo.followedOA,
      };

      setZaloProfile(nextProfile);

      const nameFieldKey = findFieldKeyByHints(formFields, NAME_FIELD_HINTS);

      await applyAutofillValues(
        {
          ...(nameFieldKey && userInfo.name
            ? { [nameFieldKey]: userInfo.name }
            : {}),
        },
        "Đã cập nhật thông tin từ Zalo",
        "Thông tin Zalo đã có, các ô hiện tại không còn trống để cập nhật",
      );
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : "Không thể kết nối thông tin Zalo";
      await showToast({ message });
    } finally {
      setIsLoadingZaloProfile(false);
    }
  };

  const handleFillPhoneFromZalo = async () => {
    setIsLoadingZaloPhone(true);

    try {
      await ensureZaloScopes(["scope.userPhonenumber"]);
      const { number, token } = await getPhoneNumber({});

      if (token) {
        setZaloPhoneToken(token);
      }

      if (number?.trim()) {
        setZaloPhoneNumber(number);

        const phoneFieldKey = findFieldKeyByHints(
          formFields,
          PHONE_FIELD_HINTS,
          "tel",
        );

        if (!phoneFieldKey) {
          await showToast({
            message: "Đã lấy số điện thoại Zalo, nhưng form này không có ô SDT",
          });
          return;
        }

        await applyAutofillValues(
          { [phoneFieldKey]: number },
          "Đã cập nhật số điện thoại từ Zalo",
          "Số điện thoại đã có, ô SDT hiện tại không còn trống để cập nhật",
        );

        return;
      }

      await showToast({
        message:
          token && !number
            ? "Đã cấp quyền SDT. ôi trường này chỉ trả token, hệ thống sẽ định danh user khi gửi form"
            : "Không lấy được số điện thoại từ Zalo",
      });
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : "Không thể lấy số điện thoại từ Zalo";
      await showToast({ message });
    } finally {
      setIsLoadingZaloPhone(false);
    }
  };

  const handleFieldChange = (fieldKey: string, value: string) => {
    setFormData((current) => ({
      ...current,
      [fieldKey]: value,
    }));
    setFieldErrors((current) => {
      const next = { ...current };
      delete next[fieldKey];
      return next;
    });
  };

  const handleContinue = async () => {
    if (!bootstrap) {
      return;
    }

    const nextErrors = getRequiredFieldErrors(formFields, formData);

    if (Object.keys(nextErrors).length > 0) {
      setFieldErrors(nextErrors);
      return;
    }

    setIsSubmitting(true);

    try {
      const rewardCodeValue = resolveRewardCodeValue(formFields, formData);
      const submission = await requestJson<SubmissionResponse>(
        `/games/${gameIdentifier}/submissions`,
        {
          method: "POST",
          body: JSON.stringify({
            payload: formData,
            zalo_profile: zaloProfile,
            zalo_phone_number: zaloPhoneNumber,
            zalo_phone_token: zaloPhoneToken,
          }),
        },
      );

      const eligibility = await requestJson<EligibilityResponse>(
        `/games/${gameIdentifier}/eligibility-check`,
        {
          method: "POST",
          body: JSON.stringify({
            player_public_id: submission.playerPublicId,
            reward_code: rewardCodeValue,
          }),
        },
      );

      if (!eligibility.eligible) {
        await showToast({
          message:
            "Trò chơi không được phép tiếp tục" + (eligibility.reason ?? ""),
        });
        return;
      }

      setPlayerPublicId(submission.playerPublicId);
      setSubmissionId(submission.submissionId);
      setRemainingSpins(eligibility.remainingSpins ?? null);
      setStage("loading");
      setLoadingProgress(18);

      progressTimer.current = window.setInterval(() => {
        setLoadingProgress((current) => {
          if (current >= 90) {
            if (progressTimer.current) {
              window.clearInterval(progressTimer.current);
              progressTimer.current = null;
            }

            return current;
          }

          return current + 7;
        });
      }, 120);

      loadingTimer.current = window.setTimeout(() => {
        if (progressTimer.current) {
          window.clearInterval(progressTimer.current);
          progressTimer.current = null;
        }
        setLoadingProgress(100);
        setStage("wheel");
      }, 1700);
    } catch (error) {
      const message =
        error instanceof Error ? error.message : "Không thể gửi thông tin";
      await showToast({ message });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSpin = async () => {
    if (isSpinning || !playerPublicId || !submissionId || prizes.length === 0) {
      return;
    }

    setIsSpinning(true);
    setWinner(null);

    try {
      const rewardCodeValue = resolveRewardCodeValue(formFields, formData);
      const idempotencyKey = buildSpinIdempotencyKey(
        playerPublicId,
        submissionId,
      );
      const response = await requestJson<SpinResponse>(
        `/games/${gameIdentifier}/spin`,
        {
          method: "POST",
          body: JSON.stringify({
            player_public_id: playerPublicId,
            player_submission_id: submissionId,
            reward_code: rewardCodeValue,
            idempotency_key: idempotencyKey,
          }),
        },
      );

      const matchedPrize = response.prize?.code
        ? (prizes.find((prize) => prize.code === response.prize?.code) ?? null)
        : null;
      const fallbackIndex = Math.floor(Math.random() * prizes.length);
      const selectedIndex = matchedPrize
        ? Math.max(
            prizes.findIndex((prize) => prize.code === matchedPrize.code),
            0,
          )
        : fallbackIndex;
      const selectedPrize = buildResultPrize(
        response,
        matchedPrize,
        paletteTones[selectedIndex % paletteTones.length],
      );

      const segmentAngle = 360 / prizes.length;
      const targetAngle = selectedIndex * segmentAngle + segmentAngle / 2;
      const pointerAngle = 0;
      const extraTurns = 360 * 6;
      const normalizedRotation = ((rotation % 360) + 360) % 360;
      const finalRotation =
        rotation +
        extraTurns +
        ((pointerAngle - targetAngle - normalizedRotation + 360) % 360);

      setPendingPrize(selectedPrize);
      setPendingResultType(response.resultType);
      setSpinResultId(response.spinResultId);
      setRemainingSpins((current) =>
        typeof current === "number" ? Math.max(0, current - 1) : current,
      );
      setRotation(finalRotation);
    } catch (error) {
      setIsSpinning(false);
      const message = error instanceof Error ? error.message : "Không thể quay";
      await showToast({ message });
    }
  };

  const handleClaimPrize = async () => {
    if (!spinResultId || isClaiming) {
      return;
    }

    setIsClaiming(true);

    try {
      const claim = await requestJson<ClaimResponse>(
        `/games/${gameIdentifier}/claim`,
        {
          method: "POST",
          body: JSON.stringify({
            spin_result_id: spinResultId,
          }),
        },
      );

      setClaimAction(claim.action ?? null);
      setClaimRedirectTarget(
        claim.redirectTarget ?? claim.metadata?.fallback_value ?? null,
      );

      await showToast({ message: "Đang mở quà của bạn..." });
      await runClaimAction(claim);
    } catch (error) {
      const message =
        error instanceof Error ? error.message : "Không thể xử lý phần thưởng";

      try {
        await showToast({ message });
      } catch {
        //
      }

      try {
        await closeApp();
      } catch {
        window.history.back();
      }
    } finally {
      setIsClaiming(false);
    }
  };

  const handleContinueAfterWin = () => {
    setWinner(null);
    setPendingPrize(null);
    setSpinResultId(null);
    setClaimAction(null);
    setClaimRedirectTarget(null);
  };

  const renderField = (field: FormField) => {
    if (field.type === "select") {
      return (
        <select
          value={formData[field.fieldKey] ?? ""}
          onChange={(event) =>
            handleFieldChange(field.fieldKey, event.target.value)
          }
        >
          <option value="">Chon</option>
          {field.options?.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      );
    }

    return (
      <input
        type={
          field.type === "email" || field.type === "tel" ? field.type : "text"
        }
        inputMode={field.type === "tel" ? "tel" : "text"}
        value={formData[field.fieldKey] ?? ""}
        onChange={(event) =>
          handleFieldChange(field.fieldKey, event.target.value)
        }
        placeholder={field.placeholder ?? ""}
      />
    );
  };

  return (
    <div
      className={`campaign-shell campaign-shell--${backgroundStyle}`}
      style={shellStyle}
    >
      <div className="campaign-backdrop campaign-backdrop-top" />
      <div className="campaign-backdrop campaign-backdrop-bottom" />

      {stage === "booting" && (
        <section className="campaign-page campaign-loading-page">
          <div className="campaign-loading-copy">{loadingLabel}</div>
          <div className="campaign-loading-track">
            <div className="campaign-loading-fill" style={{ width: "56%" }} />
          </div>
        </section>
      )}

      {stage === "unavailable" && (
        <section className="campaign-page campaign-unavailable-page">
          <div className="campaign-panel">
            <div className="campaign-panel-badge">Mini app</div>
            <h1>Lỗi</h1>
            <p>{unavailableMessage || "Vui long thu lai sau."}</p>
            <button
              type="button"
              className="campaign-primary-button"
              onClick={() => window.location.reload()}
            >
              Tải lại
            </button>
          </div>
        </section>
      )}

      {stage === "form" && bootstrap && (
        <section className="campaign-page campaign-form-page">
          <div className="campaign-hero">
            <div className="campaign-kicker">{headerTitle}</div>
            <h1>{mainTitle}</h1>
            <p>
              {content.description ??
                "Điền thông tin của bạn để nhận cơ hội quay thưởng và nhận quà tặng hấp dẫn."}
            </p>
          </div>

          <div className="campaign-panel campaign-meta-panel">
            <div className="campaign-meta-row">
              <span>Game</span>
              <strong>{bootstrap.game.slug}</strong>
            </div>
            <div className="campaign-meta-row">
              <span>Số phần thưởng</span>
              <strong>{prizes.length}</strong>
            </div>
            <div className="campaign-meta-row">
              <span>Lượt quay</span>
              <strong>{bootstrap.rules?.maxSpinsPerPlayer ?? 1}</strong>
            </div>
          </div>

          <div className="campaign-panel campaign-form-panel">
            <div className="campaign-zalo-panel">
              <div className="campaign-zalo-copy">
                <strong>Điền nhanh bằng Zalo</strong>
                <span>
                  Xin quyền để tự điền các ô còn trống, không ghi đè dữ liệu bạn
                  đã nhập.
                </span>
              </div>

              <div className="campaign-zalo-actions">
                <button
                  type="button"
                  className="campaign-secondary-button"
                  disabled={isLoadingZaloProfile}
                  onClick={() => void handleConnectZalo()}
                >
                  {isLoadingZaloProfile
                    ? "Đang kết nối..."
                    : "Đăng nhập bằng Zalo"}
                </button>

                <button
                  type="button"
                  className="campaign-secondary-button campaign-secondary-button--outline"
                  disabled={isLoadingZaloPhone}
                  onClick={() => void handleFillPhoneFromZalo()}
                >
                  {isLoadingZaloPhone
                    ? "Đang lấy số điện thoại..."
                    : "Lấy số điện thoại"}
                </button>
              </div>

              {zaloProfile ? (
                <div className="campaign-zalo-status">
                  Đã liên kết:{" "}
                  <strong>{zaloProfile.name ?? "Tài khoản Zalo"}</strong>
                </div>
              ) : null}
            </div>

            {formFields.map((field) => (
              <label key={field.fieldKey} className="campaign-field">
                <span className="campaign-field-label">
                  {field.label}
                  {field.isRequired ? "*" : ""}
                </span>
                {renderField(field)}
                {field.helpText ? (
                  <span className="campaign-field-help">{field.helpText}</span>
                ) : null}
                {fieldErrors[field.fieldKey] ? (
                  <span className="campaign-field-error">
                    {fieldErrors[field.fieldKey]}
                  </span>
                ) : null}
              </label>
            ))}

            <button
              type="button"
              className="campaign-primary-button"
              disabled={!isFormValid || isSubmitting}
              onClick={() => void handleContinue()}
            >
              {isSubmitting ? "Đang gửi..." : continueButtonLabel}
            </button>
          </div>
        </section>
      )}

      {stage === "loading" && (
        <section className="campaign-page campaign-loading-page">
          <div className="campaign-loading-copy">{loadingLabel}</div>
          <div className="campaign-loading-track">
            <div
              className="campaign-loading-fill"
              style={{ width: `${loadingProgress}%` }}
            />
          </div>
        </section>
      )}

      {stage === "wheel" && bootstrap && (
        <section className="campaign-page campaign-wheel-page">
          <div className="campaign-wheel-header">
            {bannerAssetUrl ? (
              <img
                src={bannerAssetUrl}
                alt=""
                className="campaign-banner-image"
                onError={(event) =>
                  hideBrokenImage(event, "banner(wheel)", bannerAssetUrl)
                }
              />
            ) : null}
          </div>

          <div className="campaign-wheel-scene">
            <div
              className={
                borderAssetUrl
                  ? "campaign-wheel-ring campaign-wheel-ring--custom"
                  : `campaign-wheel-ring campaign-wheel-ring--${borderPreset}`
              }
            >
              <div
                className={`campaign-wheel-rotor${borderAssetUrl ? " campaign-wheel-rotor--with-custom-border" : ""}`}
                style={{
                  transform: `rotate(${rotation}deg)`,
                  transition: isSpinning
                    ? "transform 5.2s cubic-bezier(0.18, 0.92, 0.18, 1)"
                    : undefined,
                }}
                onTransitionEnd={() => {
                  if (!isSpinning) {
                    return;
                  }

                  setIsSpinning(false);
                  setWinner(pendingPrize);
                  if (
                    pendingPrize &&
                    pendingResultType === "prize" &&
                    spinResultId
                  ) {
                    setWonPrizeHistory((current) => [
                      ...current,
                      {
                        spinResultId,
                        label: pendingPrize.label,
                        description: pendingPrize.description,
                      },
                    ]);
                  }
                }}
              >
                {borderAssetUrl ? (
                  <div
                    className="campaign-wheel-border-overlay"
                    style={{
                      background: `center / contain no-repeat url("${borderAssetUrl}")`,
                    }}
                  />
                ) : null}

                <div
                  className={`campaign-wheel${borderAssetUrl ? " campaign-wheel--with-custom-border" : ""}`}
                  style={{
                    background: buildWheelBackground(prizes),
                  }}
                >
                {prizes.map((prize, index) => (
                  <div
                    key={prize.code}
                    className="campaign-wheel-slice"
                    style={{
                      transform: `rotate(${getPrizeAngle(index, prizes.length)}deg)`,
                    }}
                  >
                    <div
                      className="campaign-wheel-slice-content"
                      style={getPrizeContentStyle(index, prizes.length)}
                    >
                      <strong>{prize.shortLabel}</strong>
                      <span>{prize.description ?? prize.label}</span>
                    </div>
                  </div>
                ))}
                </div>
              </div>
            </div>
            <div className="campaign-wheel-center">
              <div className="campaign-wheel-center-inner">
                {pointerAssetUrl ? (
                  <img
                    src={pointerAssetUrl}
                    alt=""
                    className="campaign-wheel-center-pointer"
                    onError={(event) =>
                      hideBrokenImage(event, "pointer", pointerAssetUrl)
                    }
                  />
                ) : (
                  <span>{centerLabel}</span>
                )}
              </div>
            </div>
          </div>

          <button
            type="button"
            className={`campaign-spin-button${spinButtonAssetUrl ? " campaign-spin-button--image" : ""}`}
            onClick={() => void handleSpin()}
            disabled={isSpinning}
            aria-label={spinButtonLabel}
          >
            {spinButtonAssetUrl ? (
              <img
                src={spinButtonAssetUrl}
                alt=""
                className="campaign-button-image"
                onError={(event) =>
                  hideBrokenImage(event, "spin-button", spinButtonAssetUrl)
                }
              />
            ) : (
              <span aria-hidden="true" />
            )}
          </button>

          {wonPrizeHistory.length > 0 ? (
            <section className="campaign-panel campaign-won-history">
              <div className="campaign-won-history-header">
                <strong>Quà bạn đã trúng</strong>
                <span>{wonPrizeHistory.length} phần quà</span>
              </div>
              <div className="campaign-won-history-list">
                {wonPrizeHistory.map((item) => (
                  <article
                    key={item.spinResultId}
                    className="campaign-won-history-item"
                  >
                    <div className="campaign-won-history-badge">
                      #{item.spinResultId}
                    </div>
                    <div>
                      <strong>{item.label}</strong>
                      <span>
                        {item.description ?? "Phần thưởng đã được ghi nhận."}
                      </span>
                    </div>
                  </article>
                ))}
              </div>
            </section>
          ) : null}

          {winner && (
            <div className="campaign-result-overlay">
              <div className="campaign-result-card">
                <div className="campaign-result-eyebrow">Chúc mừng!</div>
                <p>Chúc mừng bạn nhận được</p>
                <h2>{winner.label}</h2>
                <span>
                  {winner.description ??
                    "Hệ thống đã ghi nhận phần thưởng của bạn. Vui lòng nhấn nút bên dưới để nhận phần thưởng."}
                </span>
                {typeof remainingSpins === "number" ? (
                  <div className="campaign-zalo-status">
                    Còn lại <strong>{remainingSpins}</strong> lượt quay
                  </div>
                ) : null}
                {hasRemainingSpins ? (
                  <button
                    type="button"
                    className="campaign-claim-button mb-4"
                    onClick={handleContinueAfterWin}
                  >
                    {continueButtonLabel}
                  </button>
                ) : null}
                {!hasRemainingSpins && extraSpinButtonAssetUrl ? (
                  <button
                    type="button"
                    className="campaign-claim-button campaign-claim-button--image mb-4"
                    onClick={() => void handleClaimPrize()}
                    disabled={isClaiming}
                  >
                    <img
                      src={extraSpinButtonAssetUrl}
                      alt="Thêm lượt"
                      className="campaign-button-image"
                      onError={() =>
                        logAssetLoadError(
                          "extra-spin-button",
                          extraSpinButtonAssetUrl,
                        )
                      }
                    />
                  </button>
                ) : null}
                <button
                  type="button"
                  className="campaign-claim-button"
                  onClick={() => void handleClaimPrize()}
                  disabled={isClaiming}
                >
                  {isClaiming
                    ? "Đang xử lý..."
                    : claimAction === "open_oa" || claimRedirectTarget
                      ? "Nhận thưởng"
                      : "Nhận thưởng ngay"}
                </button>
              </div>
            </div>
          )}
        </section>
      )}
    </div>
  );
}
