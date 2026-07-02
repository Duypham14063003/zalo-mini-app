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
  primary_color?: string | null;
  secondary_color?: string | null;
  accent_color?: string | null;
  background_style?: string | null;
  background_asset_path?: string | null;
  background_asset_url?: string | null;
  wheel?: {
    palettePreset?: string | null;
    borderPreset?: string | null;
    borderAssetPath?: string | null;
    borderAssetUrl?: string | null;
    pointerPreset?: string | null;
    centerLabel?: string | null;
    previewNote?: string | null;
  } | null;
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
    transform: `translate(-50%, -50%) rotate(${segmentAngle / 2}deg) translateY(-122px)`,
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
      accumulator[field.fieldKey] = "Vui long nhap thong tin nay";
      return accumulator;
    }

    if (
      field.type === "select" &&
      value.length > 0 &&
      Array.isArray(field.options) &&
      !field.options.includes(value)
    ) {
      accumulator[field.fieldKey] = "Gia tri khong hop le";
      return accumulator;
    }

    if (
      field.type === "email" &&
      value.length > 0 &&
      !/\S+@\S+\.\S+/.test(value)
    ) {
      accumulator[field.fieldKey] = "Email khong hop le";
      return accumulator;
    }

    if (
      field.type === "tel" &&
      value.length > 0 &&
      !/^[0-9+\s-]{8,15}$/.test(value)
    ) {
      accumulator[field.fieldKey] = "So dien thoai khong hop le";
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
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: {
      "Content-Type": "application/json",
      "ngrok-skip-browser-warning": "true",
      ...(init?.headers ?? {}),
    },
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
  const backgroundAssetUrl =
    bootstrap?.theme?.background_asset_url?.trim() || null;
  const paletteTones = useMemo(
    () => getPaletteTones(wheelTheme?.palettePreset),
    [wheelTheme?.palettePreset],
  );
  const borderPreset = wheelTheme?.borderPreset ?? "classic-red";
  const borderAssetUrl = wheelTheme?.borderAssetUrl?.trim() || null;
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
            response.message ?? "Tro choi tam thoi chua san sang",
          );
          setStage("unavailable");
          return;
        }

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
        const message =
          error instanceof Error ? error.message : "Khong tai duoc tro choi";
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
    content.subtitle ?? bootstrap?.game.name ?? "Vong quay uu dai";
  const mainTitle = content.title ?? "Yeu Thuong";
  const spinButtonLabel = content.spin_button ?? "Quay ngay";
  const continueButtonLabel = content.continue_button ?? "Tiep tuc";
  const loadingLabel = content.loading_message ?? "Dang tai...";
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
        "Da cap nhat thong tin tu Zalo",
        "Thong tin Zalo da co, cac o hien tai khong con trong de cap nhat",
      );
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : "Khong the ket noi thong tin Zalo";
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
            message: "Da lay so dien thoai Zalo, nhung form nay khong co o SDT",
          });
          return;
        }

        await applyAutofillValues(
          { [phoneFieldKey]: number },
          "Da cap nhat so dien thoai tu Zalo",
          "So dien thoai da co, o SDT hien tai khong con trong de cap nhat",
        );

        return;
      }

      await showToast({
        message:
          token && !number
            ? "Da cap quyen SDT. Moi truong nay chi tra token, he thong se dinh danh user khi gui form"
            : "Khong lay duoc so dien thoai tu Zalo",
      });
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : "Khong the lay so dien thoai tu Zalo";
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
      const message = error instanceof Error ? error.message : "Khong the quay";
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

      await showToast({ message: "Dang mo qua cua ban..." });
      await runClaimAction(claim);
    } catch (error) {
      const message =
        error instanceof Error ? error.message : "Khong xu ly duoc phan thuong";

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
              <span>So phan thuong</span>
              <strong>{prizes.length}</strong>
            </div>
            <div className="campaign-meta-row">
              <span>Luot quay</span>
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
                    ? "Dang ket noi..."
                    : "Đăng nhập bằng Zalo"}
                </button>

                <button
                  type="button"
                  className="campaign-secondary-button campaign-secondary-button--outline"
                  disabled={isLoadingZaloPhone}
                  onClick={() => void handleFillPhoneFromZalo()}
                >
                  {isLoadingZaloPhone ? "Dang lay SDT..." : "Lấy số điện thoại"}
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
              {isSubmitting ? "Dang gui..." : continueButtonLabel}
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
            <div className="campaign-kicker">{headerTitle}</div>
            <h1>{mainTitle}</h1>
            {content.description ? (
              <p className="campaign-wheel-description">
                {content.description}
              </p>
            ) : null}
          </div>

          <div className="campaign-wheel-scene">
            <div
              className={
                borderAssetUrl
                  ? "campaign-wheel-ring campaign-wheel-ring--custom"
                  : `campaign-wheel-ring campaign-wheel-ring--${borderPreset}`
              }
              style={
                borderAssetUrl
                  ? {
                      background: `center / contain no-repeat url("${borderAssetUrl}")`,
                    }
                  : undefined
              }
            >
              <div
                className="campaign-wheel"
                style={{
                  background: buildWheelBackground(prizes),
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

                <div className="campaign-wheel-center">
                  <div className="campaign-wheel-center-inner">
                    <span>{centerLabel}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button
            type="button"
            className="campaign-spin-button"
            onClick={() => void handleSpin()}
            disabled={isSpinning}
          >
            {isSpinning ? "Dang quay..." : spinButtonLabel}
          </button>

          {previewNote ? (
            <div className="campaign-wheel-note">{previewNote}</div>
          ) : null}

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
