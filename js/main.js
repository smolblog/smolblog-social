import { AccountBlogLink } from "./components/AccountBlogLink";
import apiFetch from "@wordpress/api-fetch";

const { render, useState, useEffect } = wp.element;

const ManageConnections = () => {
  const [accounts, setAccounts] = useState([]);
  const [currentUserId, setUserId] = useState(0);

  useEffect(async () => {
    const accountResponse = await apiFetch({
      path: "/smolblog/v1/accounts/blogs",
    });
    const userResponse = await apiFetch({ path: "/wp/v2/users/me" });

    setUserId(userResponse.id);
    setAccounts(accountResponse);
  }, []);

  return (
    <div>
      <table className="widefat striped fixed">
        <colgroup>
          <col span="1" style={{ width: "50px" }} />
          <col span="1" />
          <col span="1" style={{ width: "100px" }} />
          <col span="1" style={{ width: "100px" }} />
          <col span="1" style={{ width: "100px" }} />
        </colgroup>
        <thead>
          <tr>
            <th colSpan="2">Account</th>
            <th>Push</th>
            <th>Pull</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
          {accounts.map((account) => (
            <AccountBlogLink
              key={`account-${account.id}`}
              account={account}
              currentUserId={currentUserId}
            />
          ))}
        </tbody>
      </table>
      <p style={{ textAlign: "center" }}>
        Icons from{" "}
        <a href="https://github.com/FortAwesome/Font-Awesome/tree/6.x/svgs/brands">
          FontAwesome
        </a>
      </p>
    </div>
  );
};

render(
  <ManageConnections />,
  document.getElementById("smolblog-social-connections-app")
);
