import Twitter from "./icons/Twitter";
import Tumblr from "./icons/Tumblr";
import apiFetch from "@wordpress/api-fetch";

const { useEffect, useState, Fragment } = wp.element;

const SUB_ACCOUNT_REQUIRED = ["tumblr"];

export const AccountBlogLink = (props) => {
  const [isBusy, setIsBusy] = useState(false);
  const [canPush, setCanPush] = useState(false);
  const [canPull, setCanPull] = useState(false);
  const [allSubAccounts, setAllSubAccounts] = useState([]);
  const [subAccount, setSubAccount] = useState("");
  const [isParticular, setIsParticular] = useState(false);

  const {
    account: {
      id,
      user_id: ownerId,
      social_type: type = "",
      social_username: name = "",
      link_id: linkId = 0,
      additional_info: info = "",
      can_push: push = false,
      can_pull: pull = false,
    },
    currentUserId,
    addRow,
  } = props;

  useEffect(() => {
    setCanPush(push);
    setCanPull(pull);
    setSubAccount(info);
    setIsParticular(linkId > 0);

    if (SUB_ACCOUNT_REQUIRED.includes(type)) {
      apiFetch({
        path: `/smolblog/v1/accounts/${id}/subaccounts`,
      })
        .then((res) => {
          setAllSubAccounts(res);
          if (!info) {
            setSubAccount(res[0].url);
          }
        })
        .catch((err) => console.error(err));
    }
  }, []);

  const getIcon = () => {
    switch (type) {
      case "twitter":
        return <Twitter />;
      case "tumblr":
        return <Tumblr />;
    }
    return <span />;
  };

  const disabled = () => isBusy || currentUserId != ownerId;

  const onClick = () => {
    setIsBusy(true);
    setPermissions().then(() => {
      setIsBusy(false);
      setIsParticular(true);
    });
  };

  const setPermissions = async () => {
    const result = await apiFetch({
      path: "/smolblog/v1/accounts/blogs/setpermissions",
      method: "POST",
      data: {
        social_id: id,
        additional_info: subAccount,
        push: canPush,
        pull: canPull,
      },
    });

    if (!result.success) {
      throw Error(`Error from Smolblog: ${JSON.stringify(result)}`);
    }
  };

  const dumpState = () => {
    console.log({
      id,
      name,
      info,
      allSubAccounts,
      subAccount,
      isParticular,
    });
  };

  return (
    <tr className={type}>
      <td onClick={dumpState}>{getIcon()}</td>
      <td>{name}</td>
      <td>
        {allSubAccounts.length <= 0 ? (
          <Fragment />
        ) : isParticular ? (
          <span>
            {allSubAccounts.find((sub) => sub.url == subAccount)?.name ?? ""}
          </span>
        ) : (
          <select onChange={(e) => setSubAccount(e.target.value)}>
            {allSubAccounts.map((sub) => (
              <option key={sub.name} value={sub.url}>
                {sub.name}
              </option>
            ))}
          </select>
        )}
      </td>
      <td>
        {SUB_ACCOUNT_REQUIRED.includes(type) && !disabled() && isParticular ? (
          <button
            onClick={() =>
              addRow({
                id,
                user_id: ownerId,
                social_type: type,
                social_username: name,
              })
            }
          >
            +
          </button>
        ) : (
          ""
        )}
      </td>
      <td>
        <input
          type="checkbox"
          name={`checkbox-${id}-push`}
          id={`checkbox-${id}-push`}
          checked={canPush}
          disabled={disabled()}
          onChange={() => setCanPush(!canPush)}
        />
      </td>
      <td>
        <input
          type="checkbox"
          name={`checkbox-${id}-pull`}
          id={`checkbox-${id}-pull`}
          checked={canPull}
          disabled={disabled()}
          onChange={() => setCanPull(!canPull)}
        />
      </td>
      <td>
        <button disabled={disabled()} onClick={onClick}>
          Save
        </button>
      </td>
    </tr>
  );
};
